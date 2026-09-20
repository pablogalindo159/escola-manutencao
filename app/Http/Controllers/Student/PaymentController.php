<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use MercadoPago\Client\PaymentClient;
use MercadoPago\MercadoPagoConfig;

class PaymentController extends Controller
{
    /**
     * Exibe a página de pagamento PIX Transparente
     */
    public function checkout(Request $request, Course $course)
    {
        $user = $request->user();

        if ($course->type !== 'paid' || $course->status !== 'published') {
            return back()->with('error', 'Este curso não está disponível para compra no momento.');
        }

        if ($user->courses()->where('course_id', $course->id)->exists()) {
            return redirect()->route('student.courses.show', $course);
        }

        return view('student.pix-transparente', [
            'course' => $course,
            'user' => $user,
        ]);
    }

    /**
     * API: Gera um pagamento PIX e retorna QR Code
     * POST /minha-area/cursos/{course}/gerar-pix
     */
    public function gerarPix(Request $request, Course $course)
    {
        $user = $request->user();

        if ($course->type !== 'paid' || $course->status !== 'published') {
            return response()->json(['error' => 'Curso não disponível'], 400);
        }

        // Verificar se já é aluno
        if ($user->courses()->where('course_id', $course->id)->exists()) {
            return response()->json(['error' => 'Você já tem acesso a este curso'], 400);
        }

        try {
            // Configurar SDK Mercado Pago
            $accessToken = \App\Models\Setting::get('mercado_pago_access_token') 
                ?? config('services.mercadopago.access_token');
            
            if (!$accessToken) {
                throw new \Exception('Credenciais Mercado Pago não configuradas');
            }

            MercadoPagoConfig::setAccessToken($accessToken);

            // Criar pagamento PIX no Mercado Pago
            $client = new PaymentClient();
            $payment_data = [
                'transaction_amount' => (float) $course->price,
                'description' => $course->title,
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => $user->email,
                    'first_name' => explode(' ', $user->name)[0],
                ],
                'metadata' => [
                    'course_id' => $course->id,
                    'user_id' => $user->id,
                ],
                'notification_url' => route('webhooks.mercadopago'),
            ];

            $mpPayment = $client->create($payment_data);

            // Salvar Payment no BD
            $payment = Payment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'amount' => $course->price,
                'mercado_pago_payment_id' => $mpPayment->id,
                'status' => 'pending',
                'method' => 'pix',
                'metadata' => [
                    'qr_code' => $mpPayment->point_of_interaction?->transaction_data?->qr_code,
                    'copy_paste' => $mpPayment->point_of_interaction?->transaction_data?->copy_and_paste,
                ],
            ]);

            // Retornar QR Code e dados PIX
            return response()->json([
                'payment_id' => $mpPayment->id,
                'qr_code' => $mpPayment->point_of_interaction?->transaction_data?->qr_code,
                'qr_code_base64' => $mpPayment->point_of_interaction?->transaction_data?->qr_code_base64,
                'copy_paste' => $mpPayment->point_of_interaction?->transaction_data?->copy_and_paste,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Verifica status do pagamento PIX
     * GET /minha-area/cursos/{course}/status-pix/{paymentId}
     */
    public function statusPix(Request $request, Course $course, $paymentId)
    {
        try {
            $accessToken = \App\Models\Setting::get('mercado_pago_access_token') 
                ?? config('services.mercadopago.access_token');
            
            MercadoPagoConfig::setAccessToken($accessToken);

            $client = new PaymentClient();
            $mpPayment = $client->get($paymentId);

            return response()->json([
                'status' => $mpPayment->status,
                'approved' => $mpPayment->status === 'approved',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * O Mercado Pago manda o aluno de volta pra cá depois do checkout
     */
    public function returnFromCheckout(Request $request)
    {
        $status = $request->query('status', 'pending');

        $messages = [
            'success' => ['success', 'Pagamento aprovado! Seu acesso ao curso libera em instantes.'],
            'pending' => ['warning', 'Pagamento em análise. Assim que for aprovado, o curso libera automaticamente.'],
            'failure' => ['error', 'Pagamento não foi concluído. Você pode tentar novamente quando quiser.'],
        ];

        [$type, $message] = $messages[$status] ?? $messages['pending'];

        return redirect()->route('student.dashboard')->with($type, $message);
    }
}

