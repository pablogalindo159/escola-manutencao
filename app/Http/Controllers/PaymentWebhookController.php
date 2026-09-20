<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recebe as notificações do Mercado Pago quando o status de um
 * pagamento muda. NUNCA confia no corpo da notificação em si - só usa
 * ela pra saber "olha o pagamento X", e busca os dados de verdade
 * direto na API deles antes de liberar qualquer acesso a curso.
 */
class PaymentWebhookController extends Controller
{
    public function handle(Request $request, MercadoPagoService $mercadoPago): JsonResponse
    {
        $type = $request->input('type') ?? $request->query('topic');

        // Só nos interessa notificação de pagamento; outros tipos (ex:
        // merchant_order) a gente reconhece e ignora, sem dar erro.
        if ($type && $type !== 'payment') {
            return response()->json(['ignored' => true]);
        }

        $paymentId = $request->input('data.id')
            ?? $request->query('data_id')
            ?? $request->query('id');

        if (!$paymentId) {
            Log::warning('Webhook Mercado Pago sem payment id', $request->all());
            return response()->json(['ignored' => true]);
        }

        try {
            $data = $mercadoPago->getPayment((string) $paymentId);
        } catch (\Throwable $e) {
            Log::error('Erro ao consultar pagamento do webhook Mercado Pago: ' . $e->getMessage());
            // 200 mesmo em erro - devolver erro faz o Mercado Pago ficar
            // retentando pra sempre; melhor logar e olhar manualmente.
            return response()->json(['error' => 'internal']);
        }

        $reference = $mercadoPago->parseExternalReference($data['external_reference'] ?? null);

        if (!$reference) {
            Log::warning('Webhook Mercado Pago com external_reference inválido', [
                'payment_id' => $paymentId,
                'external_reference' => $data['external_reference'] ?? null,
            ]);
            return response()->json(['ignored' => true]);
        }

        $status = $this->mapStatus($data['status'] ?? 'pending');

        // updateOrCreate pelo mercado_pago_payment_id garante que uma
        // notificação repetida (o Mercado Pago reenvia às vezes) não
        // cria pagamento duplicado nem libera acesso duas vezes.
        $payment = Payment::updateOrCreate(
            ['mercado_pago_payment_id' => (string) $data['id']],
            [
                'user_id' => $reference['user_id'],
                'course_id' => $reference['course_id'],
                'amount' => $data['transaction_amount'] ?? 0,
                'status' => $status,
                'method' => $mercadoPago->mapPaymentMethod($data['payment_type_id'] ?? null),
                'paid_at' => $status === 'approved' ? now() : null,
                'metadata' => $data,
            ]
        );

        if ($status === 'approved') {
            $subscription = Subscription::firstOrCreate(
                ['user_id' => $reference['user_id'], 'course_id' => $reference['course_id']],
                ['type' => 'lifetime', 'price' => $payment->amount, 'status' => 'active']
            );

            if ($subscription->status !== 'active') {
                $subscription->update(['status' => 'active']);
            }

            if (!$payment->subscription_id) {
                $payment->update(['subscription_id' => $subscription->id]);
            }
        }

        return response()->json(['success' => true]);
    }

    private function mapStatus(string $mpStatus): string
    {
        return match ($mpStatus) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}
