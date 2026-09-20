<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Cria a preferência de pagamento e devolve a URL de checkout do
     * Mercado Pago pro app abrir no navegador (fora do app - o
     * pagamento em si sempre acontece na página deles, nunca dentro
     * do app, então não lidamos com dado de cartão nenhum aqui).
     */
    public function checkout(Request $request, Course $course, MercadoPagoService $mercadoPago): JsonResponse
    {
        $user = $request->user();

        if ($course->type !== 'paid' || $course->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Este curso não está disponível para compra no momento.',
            ], 422);
        }

        if ($user->courses()->where('course_id', $course->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Você já tem acesso a este curso.',
            ], 422);
        }

        try {
            $preference = $mercadoPago->createPreference($course, $user);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'mercado_pago_preference_id' => $preference['id'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'init_point' => $preference['init_point'] ?? null,
            ],
        ]);
    }
}
