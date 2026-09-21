<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function mercadoPagoWebhook(Request $request)
    {
        // ============= VALIDAÇÃO HMAC =============
        $xSignature = $request->header('X-Signature');
        $xRequestId = $request->header('X-Request-Id');

        if (!$xSignature) {
            Log::warning('Webhook MP: Missing X-Signature header');
            return response()->json(['error' => 'Missing signature'], 400);
        }

        // Parsear "ts=1234567890,v1=abc123..."
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            if (strpos($part, '=') === false) continue;
            [$key, $value] = explode('=', $part);
            $parts[trim($key)] = trim($value);
        }

        $ts = $parts['ts'] ?? null;
        $v1 = $parts['v1'] ?? null;

        if (!$ts || !$v1) {
            Log::warning('Webhook MP: Invalid X-Signature format');
            return response()->json(['error' => 'Invalid signature format'], 400);
        }

        // ============= CALCULAR HMAC-SHA256 =============
        $rawBody = file_get_contents('php://input');
        $secret = config('services.mercado_pago.webhook_secret');

        if (!$secret) {
            Log::error('Webhook MP: Webhook secret not configured');
            return response()->json(['error' => 'Server error'], 500);
        }

        $calculated = hash_hmac('sha256', "$ts.$rawBody", $secret);

        // ============= VALIDAR ASSINATURA =============
        if (!hash_equals($calculated, $v1)) {
            Log::warning("Webhook MP: Invalid HMAC signature [ts={$ts}]");
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        Log::info("✅ Webhook MP válido [request_id={$xRequestId}]");

        // ============= PROCESSAR PAYLOAD =============
        $data = $request->json()->all();
        $action = $data['action'] ?? null;
        $paymentId = $data['data']['id'] ?? null;

        if (!$paymentId) {
            Log::warning('Webhook MP: Missing payment ID');
            return response()->json(['success' => true]);
        }

        Log::info("Webhook MP: action={$action}, paymentId={$paymentId}");

        // ============= BUSCAR PAGAMENTO NO BANCO =============
        $payment = Payment::where('mercado_pago_payment_id', $paymentId)->first();

        if (!$payment) {
            Log::warning("Webhook MP: Payment not found [mercado_pago_payment_id={$paymentId}]");
            return response()->json(['success' => true]);
        }

        // ============= CONSULTAR STATUS NO MERCADO PAGO =============
        $mpPaymentData = $this->getMercadoPagoPaymentData($paymentId);

        if (!$mpPaymentData) {
            Log::error("Webhook MP: Could not fetch payment from Mercado Pago [id={$paymentId}]");
            return response()->json(['success' => true]);
        }

        $mpStatus = $mpPaymentData['status'] ?? null;
        $mpStatusDetail = $mpPaymentData['status_detail'] ?? null;

        Log::info("Webhook MP: Payment status [status={$mpStatus}] [detail={$mpStatusDetail}]");

        // ============= ATUALIZAR PAGAMENTO =============
        $payment->update([
            'status' => $mpStatus,
            'mercado_pago_order_id' => $mpPaymentData['order_id'] ?? null,
            'external_reference' => $mpPaymentData['external_reference'] ?? null,
            'qr_code' => $mpPaymentData['qr_code'] ?? null,
            'qr_code_base64' => $mpPaymentData['qr_code_base64'] ?? null,
            'metadata' => json_encode([
                'webhook_action' => $action,
                'mp_status_detail' => $mpStatusDetail,
                'webhook_received_at' => now(),
            ]),
        ]);

        // ============= PROCESSAR APROVAÇÃO =============
        if ($mpStatus === 'approved') {
            Log::info("✅ Pagamento aprovado [payment_id={$payment->id}] [user_id={$payment->user_id}]");
            
            if ($payment->course_id && $payment->user_id) {
                $payment->user->courses()->syncWithoutDetaching([$payment->course_id]);
                Log::info("✅ Usuário adicionado ao curso [user={$payment->user_id}] [course={$payment->course_id}]");
            }
        } elseif ($mpStatus === 'rejected') {
            Log::warning("❌ Pagamento rejeitado [payment_id={$payment->id}] [detail={$mpStatusDetail}]");
        }

        return response()->json([
            'success' => true,
            'payment_id' => $paymentId,
            'status' => $mpStatus,
        ], 200);
    }

    private function getMercadoPagoPaymentData($paymentId)
    {
        try {
            $accessToken = config('services.mercado_pago.access_token');
            
            if (!$accessToken) {
                Log::error('Webhook: Mercado Pago access token not configured');
                return null;
            }

            $response = \Http::withToken($accessToken)
                ->timeout(10)
                ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if (!$response->successful()) {
                Log::error("Webhook: Failed to fetch payment from MP [status={$response->status()}] [id={$paymentId}]");
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Webhook: Exception fetching payment from MP [{$e->getMessage()}]");
            return null;
        }
    }
}
