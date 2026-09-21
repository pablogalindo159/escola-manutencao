<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function mercadoPagoWebhook(Request $request)
    {
        $xSignature = $request->header('X-Signature');
        $xRequestId = $request->header('X-Request-Id');
        
        // O Mercado Pago envia data.id na query string.
        // Aceitamos também data_id caso o PHP normalize o ponto.
        $dataId = (string) (
            $request->query('data.id')
            ?? $request->query('data_id')
            ?? ''
        );
        $type = (string) ($request->query('type') ?? '');

        if (!$xSignature || !$xRequestId || !$dataId) {
            Log::warning('Webhook MP: dados obrigatórios ausentes', [
                'has_x_signature' => !empty($xSignature),
                'has_x_request_id' => !empty($xRequestId),
                'has_data_id' => !empty($dataId),
                'query_keys' => array_keys($request->query()),
            ]);
            return response()->json(['error' => 'Invalid notification'], 400);
        }

        // Parsear X-Signature: ts=...,v1=...
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            $keyValue = explode('=', $part, 2);
            if (count($keyValue) !== 2) {
                continue;
            }
            $parts[trim($keyValue[0])] = trim($keyValue[1]);
        }

        $ts = $parts['ts'] ?? null;
        $v1 = $parts['v1'] ?? null;

        if (!$ts || !$v1) {
            Log::warning('Webhook MP: x-signature inválido');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Usar data.id exatamente como recebido (sem modificações)
        $signatureId = $dataId;

        // Obter secret do Admin ou config
        $secret = \App\Models\Setting::get(
            'mercado_pago_webhook_secret',
            config('services.mercadopago.webhook_secret', '')
        );

        if (!$secret) {
            Log::error('Webhook MP: secret não configurado');
            return response()->json(['error' => 'Server error'], 500);
        }

        // Manifest conforme documentação oficial
        $manifest = "id:{$signatureId};request-id:{$xRequestId};ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        if (!hash_equals($expected, $v1)) {
            Log::warning('Webhook MP: assinatura inválida', [
                'request_id' => $xRequestId,
                'type' => $type,
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->json()->all();
        $action = $data['action'] ?? null;

        // ========== ORDERS API ==========
        if ($type === 'order') {
            $orderId = $dataId;
            $order = $this->getMercadoPagoOrderData($orderId);

            if (!$order) {
                return response()->json(['success' => true], 200);
            }

            $mpPayment = $order['transactions']['payments'][0] ?? [];
            $paymentId = $mpPayment['id'] ?? null;
            $mpStatus = $mpPayment['status'] ?? $order['status'] ?? null;

            $payment = Payment::where('mercado_pago_order_id', $orderId)->first();
            if (!$payment && $paymentId) {
                $payment = Payment::where('mercado_pago_payment_id', $paymentId)->first();
            }

            if (!$payment) {
                Log::warning('Webhook MP: Order não encontrada no banco', [
                    'order_id' => $orderId,
                ]);
                return response()->json(['success' => true], 200);
            }

            $payment->update([
                'status' => $mpStatus,
                'mercado_pago_payment_id' => $paymentId ?: $payment->mercado_pago_payment_id,
                'external_reference' => $order['external_reference'] ?? $payment->external_reference,
                'metadata' => json_encode([
                    'webhook_type' => 'order',
                    'webhook_action' => $action,
                    'mp_status_detail' => $mpPayment['status_detail'] ?? $order['status_detail'] ?? null,
                    'webhook_received_at' => now()->toIso8601String(),
                ]),
            ]);

            if (in_array($mpStatus, ['approved', 'processed'], true)) {
                $payment->markAsApproved();
            }

            return response()->json(['success' => true], 200);
        }

        // ========== CHECKOUT PRO / PAYMENTS API ==========
        if ($type === 'payment') {
            $paymentId = $data['data']['id'] ?? $dataId;
            $mpPaymentData = $this->getMercadoPagoPaymentData($paymentId);

            if (!$mpPaymentData) {
                return response()->json(['success' => true], 200);
            }

            $payment = Payment::where('mercado_pago_payment_id', $paymentId)->first();

            if (!$payment) {
                Log::warning('Webhook MP: Payment não encontrado', [
                    'payment_id' => $paymentId,
                ]);
                return response()->json(['success' => true], 200);
            }

            $status = $mpPaymentData['status'] ?? null;

            $payment->update([
                'status' => $status,
                'external_reference' => $mpPaymentData['external_reference'] ?? $payment->external_reference,
                'metadata' => json_encode([
                    'webhook_type' => 'payment',
                    'webhook_action' => $action,
                    'webhook_received_at' => now()->toIso8601String(),
                ]),
            ]);

            if ($status === 'approved') {
                $payment->markAsApproved();
            }

            return response()->json(['success' => true], 200);
        }

        return response()->json(['success' => true], 200);
    }

    private function getMercadoPagoOrderData(string $orderId): ?array
    {
        try {
            $accessToken = \App\Models\Setting::get(
                'mercado_pago_access_token',
                config('services.mercadopago.access_token', '')
            );

            if (!$accessToken) {
                Log::error('Webhook: Access Token não configurado');
                return null;
            }

            $response = \Http::withToken($accessToken)
                ->timeout(10)
                ->get("https://api.mercadopago.com/v1/orders/{$orderId}");

            if (!$response->successful()) {
                Log::error('Webhook: erro ao consultar Order', [
                    'status' => $response->status(),
                    'order_id' => $orderId,
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Webhook: exceção ao consultar Order', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function getMercadoPagoPaymentData(string $paymentId): ?array
    {
        try {
            $accessToken = \App\Models\Setting::get(
                'mercado_pago_access_token',
                config('services.mercadopago.access_token', '')
            );

            if (!$accessToken) {
                Log::error('Webhook: Access Token não configurado');
                return null;
            }

            $response = \Http::withToken($accessToken)
                ->timeout(10)
                ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if (!$response->successful()) {
                Log::error('Webhook: erro ao consultar Payment', [
                    'status' => $response->status(),
                    'payment_id' => $paymentId,
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Webhook: exceção ao consultar Payment', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
