<?php

use App\Http\Controllers\PixTransparenteController;
use Illuminate\Support\Facades\Route;

// Rotas de PIX (autenticadas)
Route::middleware(['auth:sanctum'])->prefix('payments')->group(function () {
    
    // Gerar PIX direto e transparente
    Route::post('/pix/gerar', [PixTransparenteController::class, 'gerarPix']);
    
    // Verificar status do pagamento (polling)
    Route::get('/pix/{payment_id}/status', [PixTransparenteController::class, 'verificarStatus']);
    
    // Cancelar pagamento
    Route::post('/pix/{payment_id}/cancel', [PixTransparenteController::class, 'cancelarPagamento']);
    
    // Listar meus pagamentos
    Route::get('/my-payments', [PixTransparenteController::class, 'minhasPagamentos']);
});

// Webhook do Mercado Pago (sem autenticação, validado por assinatura)
Route::post('/payments/webhook', [PixTransparenteController::class, 'webhook']);
