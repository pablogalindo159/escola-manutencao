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
     */
    public function handle(Request $request)
    {
        try {
            // Log de entrada
            Log::info('Webhook Mercado Pago recebido', [
                'type' => $request->input('type'),
                'data' => $request->input('data'),
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
