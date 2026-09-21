<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * POST /api/webhooks/mercadopago
     * Webhook único para ORDERS e PAYMENTS (transição suave)
     * ✅ FASE 3: Validação HMAC com x-signature (obrigatório)
     */
    public function handle(Request $request)
    {
        try {
            // ✅ FASE 3: NOVO - Validar assinatura HMAC antes de processar
            if (!$this->validateWebhookSignature($request)) {
                Log::warning('Webhook rejeitado: assinatura inválida', [
                    'x-signature' => $request->header('x-signature'),
                    'x-request-id' => $request->header('x-request-id'),
                    'data-id' => $request->input('data.id'),
                ]);
                
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Log de entrada
            Log::info('Webhook Mercado Pago recebido (validado)', [
                'type' => $request->input('type'),
                'data' => $request->input('data'),
                'request-id' => $request->header('x-request-id'),
            ]);

            $type = $request->input('type');
            $dataId = $request->input('data.id');

            // Validar entrada
            if (!$type || !$dataId) {
                Log::warning('Webhook inválido: type ou data.id faltando', [
                    'request' => $request->all(),
                ]);

                return response()->json(['status' => 'ok'], 200);
            }

            // ✨ NOVO: Suportar Orders API
            if ($type === 'order') {
                return $this->handleOrderWebhook($dataId, $request);
            }

            // ⚠️ LEGADO: Suportar Payments API (será descontinuado)
            if ($type === 'payment') {
                return $this->handlePaymentWebhook($dataId, $request);
            }

            Log::warning('Webhook type desconhecido', ['type' => $type]);

            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::error('PaymentWebhookController::handle exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'ok'], 200);
        }
    }

    /**
     * ✅ FASE 3: NOVO - Validar assinatura HMAC do webhook
     * 
     * Formato esperado: {request-id}:{data-id}:{webhook_secret}
     * Algoritmo: SHA256
     * Comparação: hash_equals() (timing-safe)
     */
    private function validateWebhookSignature(Request $request): bool
    {
        // 1. Ler headers obrigatórios
        $signature = $request->header('x-signature');
        $requestId = $request->header('x-request-id');
        
        if (!$signature || !$requestId) {
            Log::warning('Webhook rejeitado: headers de segurança faltando', [
                'has-signature' => !empty($signature),
                'has-request-id' => !empty($requestId),
            ]);
            return false;
        }

        // 2. Ler data.id
        $dataId = $request->input('data.id');
        if (!$dataId) {
            Log::warning('Webhook rejeitado: data.id faltando');
            return false;
        }

        // 3. Obter webhook secret das settings
        $secret = \App\Models\Setting::get('mercado_pago_webhook_secret');
        if (!$secret) {
            Log::error('Webhook rejection: webhook_secret not configured in settings');
            return false;
        }

        // 4. Calcular HMAC esperado
        // Formato: {request-id}:{data-id}:{webhook-secret}
        $toHash = "{$requestId}:{$dataId}:{$secret}";
        $expectedSignature = hash('sha256', $toHash);

        // 5. Validar usando hash_equals() (timing-safe)
        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            Log::warning('Webhook rejeitado: assinatura HMAC inválida', [
                'expected' => substr($expectedSignature, 0, 16) . '...',
                'received' => substr($signature, 0, 16) . '...',
                'request-id' => $requestId,
                'data-id' => $dataId,
            ]);
        }

        return $isValid;
    }

    /**
     * ✨ NOVO: Processar webhook de ORDER (Orders API - PIX Transparente)
     * ✅ FASE 4: Consultar Order real e liberar curso se aprovado
     */
    private function handleOrderWebhook(string $orderId, Request $request): \Illuminate\Http\JsonResponse
    {
        // Buscar Payment pelo mercado_pago_order_id
        $payment = Payment::where('mercado_pago_order_id', $orderId)->first();

        if (!$payment) {
            Log::warning('Order webhook: Payment não encontrado', ['order_id' => $orderId]);
            return response()->json(['status' => 'ok'], 200);
        }

        Log::info('Order webhook processando', [
            'order_id' => $orderId,
            'payment_id' => $payment->id,
        ]);

        try {
            // ✅ FASE 4: Consultar a Order REAL da API (não confiar cegamente no webhook)
            $mercadoPago = app(\App\Services\MercadoPagoService::class);
            $response = $mercadoPago->getOrderStatus($orderId);

            // ✅ Ler status do local correto para Orders API
            $mpStatus = $response['transactions']['payments'][0]['status'] ?? null;

            Log::info('Order webhook: status obtido da API', [
                'order_id' => $orderId,
                'payment_id' => $payment->id,
                'mp_status' => $mpStatus,
            ]);

            // ✅ FASE 4: Atualizar status se aprovado
            if ($mpStatus === 'approved') {
                $payment->update([
                    'status' => 'approved',
                    'paid_at' => now(),
                ]);

                // Criar/atualizar subscription para liberar acesso
                \App\Models\Subscription::updateOrCreate(
                    [
                        'user_id' => $payment->user_id,
                        'course_id' => $payment->course_id,
                    ],
                    [
                        'status' => 'active',
                        'expires_at' => now()->addYears(1),
                    ]
                );

                Log::info('Order aprovada via webhook - PIX confirmado, curso liberado', [
                    'order_id' => $orderId,
                    'payment_id' => $payment->id,
                    'course_id' => $payment->course_id,
                    'user_id' => $payment->user_id,
                ]);

                LoggingService::paymentApproved($payment, $response);
            } else if (in_array($mpStatus, ['rejected', 'cancelled', 'refunded'])) {
                $payment->update(['status' => 'rejected']);
                
                Log::warning('Order rejeitada via webhook', [
                    'order_id' => $orderId,
                    'payment_id' => $payment->id,
                    'mp_status' => $mpStatus,
                ]);

                LoggingService::paymentFailed($payment, "Order {$mpStatus}");
            } else {
                Log::info('Order webhook: status ainda pendente', [
                    'order_id' => $orderId,
                    'payment_id' => $payment->id,
                    'mp_status' => $mpStatus,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Order webhook: erro ao consultar API', [
                'order_id' => $orderId,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * ⚠️ LEGADO: Processar webhook de PAYMENT (Payments API - será deprecado)
     * ✅ FASE 4: Atualizado para consultar Order real e liberar curso
     */
    private function handlePaymentWebhook(string $paymentId, Request $request): \Illuminate\Http\JsonResponse
    {
        // Buscar Payment pelo mercado_pago_payment_id
        $payment = Payment::where('mercado_pago_payment_id', $paymentId)->first();

        if (!$payment) {
            Log::warning('Payment webhook: Payment não encontrado', ['payment_id' => $paymentId]);
            return response()->json(['status' => 'ok'], 200);
        }

        Log::info('Payment webhook processando', [
            'payment_id' => $paymentId,
            'internal_payment_id' => $payment->id,
            'method' => $payment->method,
        ]);

        try {
            // ✅ FASE 4: Consultar a Order/Payment REAL da API (não confiar cegamente no webhook)
            $mercadoPago = app(\App\Services\MercadoPagoService::class);
            
            if ($payment->mercado_pago_order_id) {
                // PIX Transparente: consultar /v1/orders
                $response = $mercadoPago->getOrderStatus($payment->mercado_pago_order_id);
                $mpStatus = $response['transactions']['payments'][0]['status'] ?? null;
            } else {
                // Checkout Pro: consultar /v1/payments
                $response = $mercadoPago->getPaymentStatus($paymentId);
                $mpStatus = $response['status'] ?? null;
            }

            Log::info('Payment webhook: status obtido da API', [
                'payment_id' => $payment->id,
                'mp_status' => $mpStatus,
            ]);

            // ✅ FASE 4: Atualizar status se aprovado
            if ($mpStatus === 'approved') {
                $payment->update([
                    'status' => 'approved',
                    'paid_at' => now(),
                ]);

                // Criar/atualizar subscription para liberar acesso
                \App\Models\Subscription::updateOrCreate(
                    [
                        'user_id' => $payment->user_id,
                        'course_id' => $payment->course_id,
                    ],
                    [
                        'status' => 'active',
                        'expires_at' => now()->addYears(1),
                    ]
                );

                Log::info('Payment aprovado via webhook - curso liberado', [
                    'payment_id' => $payment->id,
                    'course_id' => $payment->course_id,
                    'user_id' => $payment->user_id,
                ]);

                LoggingService::paymentApproved($payment, $response);
            } else if (in_array($mpStatus, ['rejected', 'cancelled', 'refunded'])) {
                $payment->update(['status' => 'rejected']);
                
                Log::warning('Payment rejeitado via webhook', [
                    'payment_id' => $payment->id,
                    'mp_status' => $mpStatus,
                ]);

                LoggingService::paymentFailed($payment, "Payment {$mpStatus}");
            } else {
                Log::info('Payment webhook: status pendente', [
                    'payment_id' => $payment->id,
                    'mp_status' => $mpStatus,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Payment webhook: erro ao consultar API', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * 🚀 COMPLEMENTAR: Disparar verificação de status em background
     * Pode ser chamado por job/queue periodicamente
     * 
     * Uso: PaymentWebhookController->verifyPaymentStatus($paymentId)
     */
    public function verifyPaymentStatus(string $paymentId)
    {
        $payment = Payment::where('mercado_pago_payment_id', $paymentId)
            ->orWhere('id', $paymentId)
            ->first();

        if (!$payment) {
            Log::warning('verifyPaymentStatus: Payment não encontrado', ['payment_id' => $paymentId]);
            return;
        }

        // Chamar MercadoPagoService para verificar status
        $mercadoPago = app(\App\Services\MercadoPagoService::class);

        $mpResponse = $mercadoPago->getPaymentStatus($payment->mercado_pago_payment_id);
        $mpStatus = $mpResponse['status'] ?? 'unknown';

        Log::info('verifyPaymentStatus: status obtido', [
            'payment_id' => $payment->id,
            'mp_status' => $mpStatus,
        ]);

        // Se aprovado, atualizar no banco
        if ($mpStatus === 'approved' && $payment->status !== 'approved') {
            $payment->update([
                'status' => 'approved',
                'paid_at' => now(),
            ]);

            Log::info('Payment aprovado via verifyPaymentStatus', [
                'payment_id' => $payment->id,
            ]);

            // Aqui você pode disparar um evento para:
            // - Enviar email ao usuário
            // - Atualizar acesso ao curso
            // - Gerar certificado, etc.

            return true;
        }

        return false;
    }
}
