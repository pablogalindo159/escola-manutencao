<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Cria a preferência de pagamento no Mercado Pago e manda o aluno
     * pra página de checkout hospedada por eles.
     */
    public function checkout(Request $request, Course $course, MercadoPagoService $mercadoPago)
    {
        $user = $request->user();

        if ($course->type !== 'paid' || $course->status !== 'published') {
            return back()->with('error', 'Este curso não está disponível para compra no momento.');
        }

        if ($user->courses()->where('course_id', $course->id)->exists()) {
            return redirect()->route('student.courses.show', $course);
        }

        try {
            $preference = $mercadoPago->createPreference($course, $user);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'mercado_pago_preference_id' => $preference['id'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->away($preference['init_point']);
    }

    /**
     * O Mercado Pago manda o aluno de volta pra cá depois do checkout
     * (aprovado, pendente ou recusado). O acesso real ao curso é
     * liberado pelo webhook, não aqui - isso aqui é só o "obrigado"/
     * aviso pro aluno, pra não depender de ele voltar pro navegador.
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
