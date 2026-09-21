<?php

namespace App\Http\Controllers\Student;

use App\Models\Course;
use App\Models\Payment;
use App\Services\LoggingService;
use App\Services\MercadoPagoService;
use App\Traits\PaymentMethodsTrait;
use App\Exceptions\MercadoPagoException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as LogFacade;

class PaymentController
{
    use PaymentMethodsTrait;

    protected MercadoPagoService $mercadoPago;

    public function __construct(MercadoPagoService $mercadoPago)
    {
        $this->mercadoPago = $mercadoPago;
    }

    /**
     * GET /minha-area/cursos/{course}/checkout
     */
    public function checkout(Request $request, Course $course)
    {
        $user = Auth::user();

        $hasAccess = Payment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['approved', 'paid'])
            ->exists();

        if ($hasAccess) {
            return redirect()->route('student.course.show', $course->id)
                ->with('success', 'Você já tem acesso a este curso!');
        }

        $paymentMethod = $this->getPaymentMethod();

        $view = $paymentMethod === 'pix_transparent'
            ? 'student.pix-transparente'
            : 'student.checkout-pro';

        if (!view()->exists($view)) {
            LogFacade::warning('View não existe', ['view' => $view]);
            $view = 'student.pix-transparente';
        }

        return view($view, compact('course'));
    }

    /**
     * POST /minha-area/cursos/{course}/gerar-pix
     */
    public function gerarPix(Request $request, Course $course)
    {
        try {
            $user = Auth::user();

            $existingPayment = Payment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($existingPayment && $existingPayment->status === 'approved') {
                return response()->json([
                    'error' => 'Você já comprou este curso',
                ], 400);
            }

            if (empty($this->mercadoPago->accessToken())) {
                throw new MercadoPagoException(MercadoPagoException::TYPE_INVALID_TOKEN);
            }

            LoggingService::apiCallStarted('/v1/orders', 'POST');
            
            $startTime = microtime(true);
            $orderResponse = $this->mercadoPago->createOrderPix(
                courseId: $course->id,
                courseTitle: $course->title,
                courseDescription: $course->description,
                amount: (float) $course->price,
                payerName: $user->name,
                payerEmail: $user->email
            );
            
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            LoggingService::apiCallCompleted('/v1/orders', 200, $durationMs);

            if (empty($orderResponse['id']) || empty($orderResponse['payments'])) {
                throw new MercadoPagoException(
                    MercadoPagoException::TYPE_INVALID_RESPONSE,
                    $orderResponse
                );
            }

            $orderId = $orderResponse['id'];
            $payment = $orderResponse['payments'][0] ?? null;

            if (!$payment || empty($payment['id'])) {
                throw new MercadoPagoException(
                    MercadoPagoException::TYPE_INVALID_RESPONSE,
                    $orderResponse
                );
            }

            $paymentId = $payment['id'];

            $qrData = MercadoPagoService::extractQrCodeFromOrder($orderResponse);

            if (!$qrData || !$qrData['qr_code']) {
                throw new MercadoPagoException(
                    MercadoPagoException::TYPE_INVALID_RESPONSE,
                    $payment
                );
            }

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

            LoggingService::paymentCreated($paymentRecord, [
                'mp_order_id' => $orderId,
                'mp_payment_id' => $paymentId,
            ]);

            \App\Jobs\CheckPaymentStatus::dispatch($paymentRecord)
                ->delay(now()->addSeconds(5));

            return response()->json([
                'payment_id' => $paymentRecord->id,
                'order_id' => $orderId,
                'qr_code' => $qrData['qr_code'],
                'qr_code_base64' => $qrData['qr_code_base64'],
            ], 200);

        } catch (MercadoPagoException $e) {
            LoggingService::paymentFailed(null, $e->getMessage(), ['type' => $e->type]);

            return response()->json(
                $e->toJson(),
                $e->getHttpStatusCode()
            );

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            LogFacade::error('Connection error to Mercado Pago', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'connection_error',
                'message' => 'Erro de conexão com Mercado Pago',
            ], 503);

        } catch (\Exception $e) {
            LogFacade::error('Unexpected error in gerarPix', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'internal_error',
                'message' => 'Erro interno do servidor',
            ], 500);
        }
    }

    /**
     * GET /minha-area/cursos/{course}/status-pix/{paymentId}
     */
    public function statusPix(Request $request, Course $course, string $paymentId)
    {
        try {
            $user = Auth::user();

            $payment = Payment::where('id', $paymentId)
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if (!$payment) {
                return response()->json([
                    'error' => 'Pagamento não encontrado',
                ], 404);
            }

            if ($payment->status === 'approved') {
                return response()->json([
                    'status' => 'approved',
                    'message' => 'Pagamento aprovado! Acesso liberado.',
                ], 200);
            }

            if ($payment->status === 'rejected') {
                return response()->json([
                    'status' => 'rejected',
                    'message' => 'Pagamento foi recusado.',
                ], 200);
            }

            return response()->json([
                'status' => 'pending',
                'message' => 'Aguardando confirmação do pagamento...',
            ], 200);

        } catch (\Exception $e) {
            LogFacade::error('Error in statusPix', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao verificar status',
            ], 500);
        }
    }

    /**
     * GET /checkout/retorno
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
}
