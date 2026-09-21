<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Subscription;
use App\Services\LoggingService;
use App\Services\MercadoPagoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckPaymentStatus implements ShouldQueue
{
    use Queueable;

    public int $tries = 30;
    public int $maxExceptions = 3;
    public int $timeout = 5;

    public function __construct(private Payment $payment)
    {
        $this->delay = 1;
    }

    public function handle(MercadoPagoService $mercadoPago): void
    {
        $paymentId = $this->payment->mercado_pago_payment_id;

        if (!$paymentId) {
            LoggingService::paymentFailed($this->payment, 'No Mercado Pago payment ID');
            $this->fail(new \Exception('No Mercado Pago payment ID'));
            return;
        }

        try {
            $response = $mercadoPago->getPaymentStatus($paymentId);

            if (!isset($response['status'])) {
                LoggingService::paymentFailed($this->payment, 'Invalid response from Mercado Pago', $response);
                $this->release(5);
                return;
            }

            $mpStatus = $response['status'];

            if ($mpStatus === 'approved') {
                $this->payment->update([
                    'status' => 'approved',
                    'paid_at' => now(),
                ]);

                Subscription::updateOrCreate(
                    [
                        'user_id' => $this->payment->user_id,
                        'course_id' => $this->payment->course_id,
                    ],
                    [
                        'status' => 'active',
                        'expires_at' => now()->addYears(1),
                    ]
                );

                LoggingService::paymentApproved($this->payment, $response);
                return;
            }

            if (in_array($mpStatus, ['rejected', 'cancelled', 'refunded'])) {
                $this->payment->update(['status' => 'rejected']);
                LoggingService::paymentFailed($this->payment, "Payment {$mpStatus}", $response);
                $this->fail(new \Exception("Payment {$mpStatus}"));
                return;
            }

            if ($mpStatus === 'pending') {
                $this->release(5);
                return;
            }

            Log::warning('Unknown payment status', [
                'payment_id' => $this->payment->id,
                'mp_status' => $mpStatus,
            ]);
            $this->release(5);

        } catch (\Exception $e) {
            LoggingService::apiCallError(
                '/v1/payments/{$paymentId}',
                $e->getMessage()
            );
            $this->release(5);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->payment->update(['status' => 'failed']);

        Log::error('Payment check job failed after max attempts', [
            'payment_id' => $this->payment->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
