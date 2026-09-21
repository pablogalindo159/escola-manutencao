<?php

namespace App\Http\Controllers\Student;

use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as LogFacade;

class PaymentController
{
    protected MercadoPagoService $mercadoPago;

    public function __construct(MercadoPagoService $mercadoPago)
    {
        $this->mercadoPago = $mercadoPago;
    }

    /**
     * GET /minha-area/cursos/{course}
     * Rota inicial: redireciona para PIX ou Checkout Pro
     */
    public function checkout(Request $request, Course $course)
    {
        $user = Auth::user();

        // Verificar se já comprou
        $existingPayment = Payment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['approved', 'paid'])
            ->first();

        if ($existingPayment) {
            return redirect()->route('student.course.show', $course->id)
                ->with('success', 'Você já tem acesso a este curso!');
        }

        // Determinar método: PIX ou Checkout Pro
        $paymentMethod = $this->getPaymentMethod();

        if ($paymentMethod === 'pix_transparent') {
            return view('student.pix-transparente', compact('course'));
        }

        // Checkout Pro - tentar carregar view, se não existir redireciona
        if (view()->exists('student.checkout-pro')) {
            return view('student.checkout-pro', compact('course'));
        }

        // Fallback: se view não existir, usar PIX Transparente
        LogFacade::warning('View checkout-pro não existe, usando pix-transparente como fallback', [
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);

        return view('student.pix-transparente', compact('course'));
    }

    /**
     * POST /minha-area/cursos/{course}/gerar-pix
     * 🚀 NOVO: Gera Order com PIX Transparente via Orders API
     */
    public function gerarPix(Request $request, Course $course)
    {
        try {
            $user = Auth::user();

            // Validar se já tem pagamento
            $existingPayment = Payment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($existingPayment && $existingPayment->status === 'approved') {
                return response()->json([
                    'error' => 'Você já comprou este curso',
                ], 400);
            }

            // ✨ CHAMAR ORDERS API
            $orderResponse = $this->mercadoPago->createOrderPix(
                courseId: $course->id,
                courseTitle: $course->title,
                courseDescription: $course->description,
                amount: (float) $course->price,
                payerName: $user->name,
                payerEmail: $user->email
            );

            // Validar resposta
            if (empty($orderResponse['id'])) {
                LogFacade::error('Orders API error', [
                    'course_id' => $course->id,
                    'user_id' => $user->id,
                    'response' => $orderResponse,
                ]);

                return response()->json([
                    'error' => 'Erro ao gerar QR Code PIX',
                    'details' => $orderResponse['message'] ?? 'Resposta inválida',
                ], 500);
            }

            $orderId = $orderResponse['id'];
            $payment = $orderResponse['payments'][0] ?? null;

            if (!$payment || empty($payment['id'])) {
                LogFacade::error('Orders API: payment not found', [
                    'order_id' => $orderId,
                    'response' => $orderResponse,
                ]);

                return response()->json([
                    'error' => 'Payment não encontrado na resposta',
                ], 500);
            }

            $paymentId = $payment['id'];

            // Extrair QR Code
            $qrData = MercadoPagoService::extractQrCodeFromOrder($orderResponse);

            if (!$qrData || !$qrData['qr_code']) {
                LogFacade::error('Orders API: QR Code not found', [
                    'order_id' => $orderId,
                    'payment_id' => $paymentId,
                    'payment_response' => $payment,
                ]);

                return response()->json([
                    'error' => 'QR Code não gerado',
                ], 500);
            }

            // 💾 Salvar Payment no banco
            $paymentRecord = Payment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $course->price,
                'mercado_pago_order_id' => $orderId,
                'mercado_pago_payment_id' => $paymentId,
                'status' => 'pending',
                'method' => 'pix_transparent',
                'metadata' => [
                    'order_response' => $orderResponse,
                ],
            ]);

            LogFacade::info('Payment criado via Orders API', [
                'payment_id' => $paymentRecord->id,
                'mp_order_id' => $orderId,
                'mp_payment_id' => $paymentId,
            ]);

            // Retornar QR Code para frontend
            return response()->json([
                'payment_id' => $paymentRecord->id,
                'order_id' => $orderId,
                'qr_code' => $qrData['qr_code'],
                'qr_code_base64' => $qrData['qr_code_base64'],
            ], 200);

        } catch (\Exception $e) {
            LogFacade::error('PaymentController::gerarPix exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Erro interno ao gerar PIX',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /minha-area/cursos/{course}/status-pix/{paymentId}
     * Verifica status do pagamento (polling frontend)
     */
    public function statusPix(Request $request, Course $course, string $paymentId)
    {
        try {
            $user = Auth::user();

            // Buscar Payment no banco
            $payment = Payment::where('id', $paymentId)
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if (!$payment) {
                return response()->json([
                    'error' => 'Pagamento não encontrado',
                ], 404);
            }

            // Se já aprovado, retornar status
            if ($payment->status === 'approved') {
                return response()->json([
                    'status' => 'approved',
                    'payment_id' => $payment->id,
                    'message' => 'Pagamento aprovado! Acesso liberado.',
                ], 200);
            }

            // Consultar status na API MP
            $mpPaymentId = $payment->mercado_pago_payment_id;

            if (!$mpPaymentId) {
                return response()->json([
                    'status' => 'pending',
                    'message' => 'Aguardando pagamento...',
                ], 200);
            }

            // Chamar API MP
            $mpResponse = $this->mercadoPago->getPaymentStatus($mpPaymentId);

            $mpStatus = $mpResponse['status'] ?? 'unknown';

            // Atualizar banco se houver mudança
            if ($mpStatus === 'approved' && $payment->status !== 'approved') {
                $payment->update([
                    'status' => 'approved',
                    'paid_at' => now(),
                ]);

                LogFacade::info('Payment aprovado via polling', [
                    'payment_id' => $payment->id,
                    'mp_payment_id' => $mpPaymentId,
                ]);

                return response()->json([
                    'status' => 'approved',
                    'message' => 'Pagamento aprovado! Acesso liberado.',
                ], 200);
            }

            return response()->json([
                'status' => $mpStatus,
                'message' => 'Aguardando pagamento...',
            ], 200);

        } catch (\Exception $e) {
            LogFacade::error('PaymentController::statusPix exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao verificar status',
            ], 500);
        }
    }

    /**
     * GET /checkout/retorno
     * Callback do Checkout Pro
     */
    public function returnFromCheckout(Request $request)
    {
        $status = $request->query('status');

        if ($status === 'approved') {
            return view('student.checkout-success')
                ->with('message', 'Pagamento aprovado com sucesso!');
        }

        if ($status === 'failure') {
            return view('student.checkout-failure')
                ->with('message', 'Pagamento foi recusado.');
        }

        return view('student.checkout-pending')
            ->with('message', 'Pagamento em análise.');
    }

    /**
     * Determinar método de pagamento (PIX ou Checkout Pro)
     * Lê da Setting.payment_method
     */
    private function getPaymentMethod(): string
    {
        $paymentMethod = \App\Models\Setting::where('key', 'payment_method')
            ->first()?->value;

        return $paymentMethod === 'pix_transparent' ? 'pix_transparent' : 'checkout_pro';
    }
}
