<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Services\MercadoPagoService;
use App\Traits\PaymentMethodsTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pagamentos do APP (Sanctum). Mesma lógica do site
 * (Student\PaymentController), respondendo em JSON no formato
 * { success, data | message } que o app Flutter espera.
 */
class PaymentController extends Controller
{
    use PaymentMethodsTrait;

    /**
     * GET /api/payment-method — método escolhido no admin
     * (pix_transparente ou checkout_pro), mesma config do site.
     */
    public function method(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ['method' => $this->getPaymentMethod()],
        ]);
    }

    /**
     * Verificações comuns antes de cobrar. Retorna resposta de erro ou null.
     */
    private function validarCompra(Request $request, Course $course): ?JsonResponse
    {
        if ($course->type !== 'paid' || $course->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Este curso não está disponível para compra no momento.',
            ], 422);
        }

        if ($request->user()->courses()->where('course_id', $course->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Você já tem acesso a este curso.',
            ], 422);
        }

        return null;
    }

    /**
     * POST /api/courses/{course}/checkout — Checkout Pro (cartão/boleto).
     * Corrigido: createPreference() recebe array (igual ao site).
     */
    public function checkout(Request $request, Course $course, MercadoPagoService $mercadoPago): JsonResponse
    {
        if ($erro = $this->validarCompra($request, $course)) {
            return $erro;
        }

        $user = $request->user();
        $externalReference = "checkout_pro_{$course->id}_" . Str::uuid();

        try {
            $preference = $mercadoPago->createPreference([
                'course_id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'amount' => (float) $course->price,
                'payer_name' => $user->name,
                'payer_email' => $user->email,
                'external_reference' => $externalReference,
            ]);
        } catch (\Throwable $e) {
            Log::error('API checkout: erro ao criar preference', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao iniciar pagamento. Tente novamente.',
            ], 500);
        }

        if (empty($preference['init_point']) || empty($preference['id'])) {
            Log::error('API checkout: resposta inválida do Mercado Pago', ['response' => $preference]);
            return response()->json([
                'success' => false,
                'message' => 'Resposta inválida do Mercado Pago.',
            ], 502);
        }

        $payment = Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'mercado_pago_preference_id' => $preference['id'],
            'external_reference' => $externalReference,
            'status' => 'pending',
            'method' => 'checkout_pro',
            'metadata' => ['preference_response' => $preference],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'init_point' => $preference['init_point'],
                'payment_id' => $payment->id,
            ],
        ]);
    }

    /**
     * POST /api/courses/{course}/pix — PIX Transparente (Orders API).
     * Mesma lógica de Student\PaymentController::gerarPix.
     */
    public function pix(Request $request, Course $course, MercadoPagoService $mercadoPago): JsonResponse
    {
        if ($erro = $this->validarCompra($request, $course)) {
            return $erro;
        }

        $user = $request->user();

        try {
            $orderResponse = $mercadoPago->createOrderPix(
                courseId: $course->id,
                courseTitle: $course->title,
                courseDescription: $course->description,
                amount: (float) $course->price,
                payerName: $user->name,
                payerEmail: $user->email
            );
        } catch (\Throwable $e) {
            Log::error('API pix: erro ao criar order', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PIX. Tente novamente.',
            ], 500);
        }

        if (empty($orderResponse['id']) || empty($orderResponse['transactions']['payments'][0]['id'])) {
            Log::error('API pix: resposta inválida', ['response' => $orderResponse]);
            return response()->json([
                'success' => false,
                'message' => 'Resposta inválida do Mercado Pago.',
            ], 502);
        }

        $qrData = MercadoPagoService::extractQrCodeFromOrder($orderResponse);

        if (!$qrData || empty($qrData['qr_code'])) {
            Log::error('API pix: QR Code ausente', ['response' => $orderResponse]);
            return response()->json([
                'success' => false,
                'message' => 'Não foi possível gerar o QR Code.',
            ], 502);
        }

        $payment = Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'mercado_pago_order_id' => $orderResponse['id'],
            'mercado_pago_payment_id' => $orderResponse['transactions']['payments'][0]['id'],
            'external_reference' => $orderResponse['external_reference'] ?? null,
            'status' => 'pending',
            'method' => 'pix_transparente',
            'metadata' => ['order_response' => $orderResponse],
        ]);

        \App\Jobs\CheckPaymentStatus::dispatch($payment)->delay(now()->addSeconds(5));

        return response()->json([
            'success' => true,
            'data' => [
                'payment_id' => $payment->id,
                'qr_code' => $qrData['qr_code'],
                'qr_code_base64' => $qrData['qr_code_base64'] ?? null,
                'amount' => (float) $course->price,
            ],
        ]);
    }

    /**
     * GET /api/payments/{payment}/status — app consulta a cada poucos segundos.
     */
    public function status(Request $request, int $payment): JsonResponse
    {
        $record = Payment::where('id', $payment)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Pagamento não encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => in_array($record->status, ['approved', 'paid']) ? 'approved' : $record->status,
                'course_id' => $record->course_id,
            ],
        ]);
    }
}
