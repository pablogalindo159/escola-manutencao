<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class LoggingService
{
    public static function paymentCreated(Payment $payment, array $metadata = []): void
    {
        Log::channel('payments')->info('Payment created', array_merge([
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'course_id' => $payment->course_id,
            'amount' => $payment->amount,
            'method' => $payment->method,
            'status' => $payment->status,
            'timestamp' => now()->toIso8601String(),
        ], $metadata));
    }

    public static function paymentApproved(Payment $payment, array $mpResponse): void
    {
        Log::channel('payments')->notice('Payment approved', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'course_id' => $payment->course_id,
            'mp_payment_id' => $payment->mercado_pago_payment_id,
            'mp_status' => $mpResponse['status'] ?? 'unknown',
            'approval_time_seconds' => now()->diffInSeconds($payment->created_at),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public static function paymentFailed(?Payment $payment, string $reason, ?array $mpResponse = null): void
    {
        $logData = [
            'reason' => $reason,
            'mp_response' => $mpResponse,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($payment) {
            $logData['payment_id'] = $payment->id;
            $logData['user_id'] = $payment->user_id;
            $logData['course_id'] = $payment->course_id;
            $logData['mp_payment_id'] = $payment->mercado_pago_payment_id;
        }

        Log::channel('payments')->error('Payment failed', $logData);
    }

    public static function apiCallStarted(string $endpoint, string $method = 'POST'): void
    {
        Log::channel('mercado-pago')->debug('API call started', [
            'endpoint' => $endpoint,
            'method' => $method,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public static function apiCallCompleted(string $endpoint, int $statusCode, int $durationMs): void
    {
        Log::channel('mercado-pago')->notice('API call completed', [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public static function apiCallError(string $endpoint, string $error, ?array $response = null): void
    {
        Log::channel('mercado-pago')->error('API call error', [
            'endpoint' => $endpoint,
            'error' => $error,
            'response' => $response,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
