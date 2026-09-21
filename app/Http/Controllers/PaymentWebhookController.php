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
     * ✨ NOVO: Processar webhook de ORDER (Orders API)
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

        // ⚠️ Nota: O webhook de order é genérico
        // Precisamos fazer polling do status da order/payment para confirmar aprovação
        // Isso é feito via statusPix() no frontend (polling)
        // ou podemos disparar uma job para verificar em background

        // Para segurança máxima, recomendo validar via API
        // Mas por enquanto, apenas logar que foi recebido

        Log::info('Order webhook: aguardando validação via API', [
            'order_id' => $orderId,
        ]);

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * ⚠️ LEGADO: Processar webhook de PAYMENT (Payments API - será deprecado)
     */
    private function handlePaymentWebhook(string $paymentId, Request $request): \Illuminate\Http\JsonResponse
    {
        // Buscar Payment pelo mercado_pago_payment_id
        $payment = Payment::where('mercado_pago_payment_id', $paymentId)->first();

        if (!$payment) {
            Log::warning('Payment webhook: Payment não encontrado', ['payment_id' => $paymentId]);
            return response()->json(['status' => 'ok'], 200);
        }

        Log::info('Payment webhook processando (LEGADO)', [
            'payment_id' => $paymentId,
            'internal_payment_id' => $payment->id,
        ]);

        // Chamar API para obter status completo
        // (webhook só traz ID, não traz status)
        // Isso é feito em background job ou via polling

        Log::info('Payment webhook recebido (validação pendente)', [
            'payment_id' => $paymentId,
        ]);

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
