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
        try {
            // ✅ NOVO: Detectar qual API foi usada baseado no método
            if ($this->payment->mercado_pago_order_id) {
                // PIX Transparente (Orders API) - FASE 1 novO
                $orderId = $this->payment->mercado_pago_order_id;
                $response = $mercadoPago->getOrderStatus($orderId);

                if (!isset($response['transactions']['payments'][0])) {
                    LoggingService::paymentFailed($this->payment, 'Invalid Order response from Mercado Pago', $response);
                    $this->release(5);
                    return;
                }

                // ✅ Ler status do local correto (Orders API)
                $mpStatus = $response['transactions']['payments'][0]['status'] ?? null;
                
            } else {
                // Checkout Pro (Preferences/Payments API) - fallback compatibilidade
                $paymentId = $this->payment->mercado_pago_payment_id;

                if (!$paymentId) {
                    LoggingService::paymentFailed($this->payment, 'No Mercado Pago payment ID or Order ID');
                    $this->fail(new \Exception('No Mercado Pago payment ID'));
                    return;
                }

                $response = $mercadoPago->getPaymentStatus($paymentId);

                if (!isset($response['status'])) {
                    LoggingService::paymentFailed($this->payment, 'Invalid response from Mercado Pago', $response);
                    $this->release(5);
                    return;
                }

                // ✅ Ler status do local correto (Payments API)
                $mpStatus = $response['status'];
            }

            if (!$mpStatus) {
                LoggingService::paymentFailed($this->payment, 'Status não encontrado na resposta da API', $response);
                $this->release(5);
                return;
            }

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
            $endpoint = $this->payment->mercado_pago_order_id 
                ? "/v1/orders/{$this->payment->mercado_pago_order_id}"
                : "/v1/payments/{$this->payment->mercado_pago_payment_id}";
                
            LoggingService::apiCallError(
                $endpoint,
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
