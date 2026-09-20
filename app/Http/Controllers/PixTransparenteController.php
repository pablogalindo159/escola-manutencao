<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MercadoPago\Client\PaymentClient;
use MercadoPago\Client\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment as MercadoPagoPayment;

class PixTransparenteController extends Controller
{
    public function __construct()
    {
        // Configurar credenciais do Mercado Pago
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
    }

    /**
     * Gerar PIX direto e transparente
     * POST /api/payments/pix/gerar
     * 
     * Body:
     * {
     *   "course_id": 1,
     *   "user_id": (auth user),
     *   "description": "Curso de Manutenção",
     *   "amount": 99.90
     * }
     */
    public function gerarPix(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        try {
            $user = auth()->user();
            
            // Criar Payment record no BD (usando campos válidos da migration)
            $payment = Payment::create([
                'user_id' => $user->id,
                'course_id' => $request->course_id,
                'amount' => $request->amount,
                'status' => 'pending',
                'method' => 'pix',
                'metadata' => [
                    'description' => $request->description,
                ],
            ]);

            // Criar pagamento PIX no Mercado Pago
            $client = new PaymentClient();
            $request_body = [
                "transaction_amount" => (float) $request->amount,
                "description" => $request->description,
                "payment_method_id" => "pix",
                "payer" => [
                    "email" => $user->email,
                    "first_name" => $user->name,
                ],
                "notification_url" => config('app.url') . '/api/payments/webhook',
            ];

            $payment_response = $client->create($request_body);

            // Extrair dados PIX da resposta
            $qr_code = $payment_response->point_of_interaction?->qr_code?->image ?? null;
            $pix_copy_paste = $payment_response->point_of_interaction?->qr_code?->in_store_order_id ?? null;

            // Atualizar Payment com dados do Mercado Pago
            $payment->update([
                'mercado_pago_payment_id' => $payment_response->id,
                'metadata' => [
                    'description' => $request->description,
                    'qr_code' => $qr_code,
                    'pix_copy_paste' => $pix_copy_paste,
                ],
            ]);

            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'mercado_pago_payment_id' => $payment_response->id,
                'qr_code' => $qr_code, // URL da imagem QR Code
                'pix_copy_paste' => $pix_copy_paste, // Código PIX Copia e Cola
                'amount' => $request->amount,
                'description' => $request->description,
                'expires_at' => $payment->expires_at,
                'user_name' => $user->name,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PIX: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar status do pagamento PIX
     * GET /api/payments/pix/{payment_id}/status
     */
    public function verificarStatus($payment_id)
    {
        try {
            $payment = Payment::findOrFail($payment_id);

            // Verificar se o usuário autenticado é o dono do pagamento
            if ($payment->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autorizado',
                ], 403);
            }

            // Buscar status no Mercado Pago
            $client = new PaymentClient();
            $mp_payment = $client->get($payment->mercado_pago_payment_id);

            $status = $mp_payment->status; // approved, pending, rejected, etc

            // Atualizar status no BD
            if ($status === 'approved') {
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                ]);

                // Criar Subscription automática
                Subscription::firstOrCreate(
                    [
                        'user_id' => $payment->user_id,
                        'course_id' => $payment->course_id,
                    ],
                    [
                        'enrolled_at' => now(),
                    ]
                );
            } elseif (in_array($status, ['rejected', 'cancelled'])) {
                $payment->update(['status' => 'failed']);
            }

            return response()->json([
                'success' => true,
                'status' => $payment->status,
                'mercado_pago_status' => $status,
                'paid_at' => $payment->paid_at,
                'message' => $status === 'approved' ? 'Pagamento confirmado!' : 'Aguardando pagamento...',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook do Mercado Pago
     * POST /api/payments/webhook
     */
    public function webhook(Request $request)
    {
        try {
            // Validar assinatura do webhook (opcional mas recomendado)
            $signature = $request->header('x-signature');
            $request_id = $request->header('x-request-id');

            if (!$signature || !$request_id) {
                return response()->json(['received' => true], 200);
            }

            // Processar notificação
            if ($request->action === 'payment.created' || $request->action === 'payment.updated') {
                $mp_payment_id = $request->data['id'] ?? null;

                if ($mp_payment_id) {
                    $payment = Payment::where('mercado_pago_payment_id', $mp_payment_id)->first();

                    if ($payment) {
                        // Buscar detalhes do pagamento
                        $client = new PaymentClient();
                        $mp_payment = $client->get($mp_payment_id);

                        if ($mp_payment->status === 'approved') {
                            $payment->update([
                                'status' => 'completed',
                                'paid_at' => now(),
                            ]);

                            // Inscrever usuário no curso
                            Subscription::firstOrCreate(
                                [
                                    'user_id' => $payment->user_id,
                                    'course_id' => $payment->course_id,
                                ],
                                ['enrolled_at' => now()]
                            );
                        }
                    }
                }
            }

            return response()->json(['received' => true], 200);

        } catch (\Exception $e) {
            \Log::error('Webhook Mercado Pago error: ' . $e->getMessage());
            return response()->json(['received' => true], 200); // Sempre retorna 200 pro MP
        }
    }

    /**
     * Cancelar pagamento
     * POST /api/payments/pix/{payment_id}/cancel
     */
    public function cancelarPagamento($payment_id)
    {
        try {
            $payment = Payment::findOrFail($payment_id);

            if ($payment->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autorizado',
                ], 403);
            }

            // Se ainda está pending, cancelar
            if ($payment->status === 'pending') {
                $payment->update(['status' => 'cancelled']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pagamento cancelado',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar pagamentos do usuário
     * GET /api/payments/my-payments
     */
    public function minhasPagamentos()
    {
        $payments = auth()->user()->payments()->latest()->get();

        return response()->json([
            'success' => true,
            'payments' => $payments,
        ], 200);
    }
}
