<?php

use App\Http\Controllers\Api\{
    AuthController,
    CourseController,
    VideoController,
    RepairController,
    PostController,
    CommentController,
    CertificateController,
    LiveStreamController,
    VideoStreamController,
    PaymentController,
};
use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| API routes - prefix: /api
| Middleware: api, auth:api (onde necessário)
*/

// Rotas públicas (sem autenticação)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Webhook do Mercado Pago (chamado pelo servidor deles, nunca pelo app/site -
// sem autenticação de usuário, e a rota 'api' já não tem CSRF por padrão)
Route::post('/webhooks/mercadopago', [PaymentWebhookController::class, 'handle'])->name('webhooks.mercadopago');

// Rotas de cursos públicas
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/featured', [CourseController::class, 'featured']);
// IMPORTANTE: /courses/my-courses precisa vir ANTES de /courses/{course},
// senão o Laravel casa "my-courses" como se fosse o {course} (id/slug) e
// nunca chega no controller correto (retorna 404 silenciosamente).
Route::get('/courses/my-courses', [CourseController::class, 'myCourses'])->middleware('auth:api');
Route::get('/courses/{course}', [CourseController::class, 'show']);

// Verificar certificado público
Route::get('/certificates/verify/{number}', [CertificateController::class, 'verify']);

// ==================== TRANSMISSÕES AO VIVO (públicas) ====================
Route::prefix('live-streams')->group(function () {
    Route::get('/', [LiveStreamController::class, 'index']);
    Route::get('/live-now', [LiveStreamController::class, 'liveNow']);
    Route::get('/upcoming', [LiveStreamController::class, 'upcoming']);
    Route::get('/recent', [LiveStreamController::class, 'recent']);
    Route::get('/{id}', [LiveStreamController::class, 'show']);
    Route::get('/{id}/stats', [LiveStreamController::class, 'stats']);
});

// Stream de vídeo: o token no query string É a autenticação, por isso é publica
// (players de vídeo em geral não conseguem enviar cabeçalho Authorization)
Route::get('/videos/{id}/stream', [VideoStreamController::class, 'stream'])->name('videos.stream');

/*
|--------------------------------------------------------------------------
| Rotas Protegidas (requerem JWT)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {
    
    // ==================== AUTENTICAÇÃO ====================
    Route::prefix('auth')->group(function () {
        Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/token-status', [AuthController::class, 'tokenStatus']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });

    // ==================== CURSOS ====================
    Route::prefix('courses')->group(function () {
        Route::post('/{course}/subscribe', [CourseController::class, 'subscribe']);
        Route::post('/{course}/checkout', [PaymentController::class, 'checkout']);

        // ADMIN
        Route::post('/', [CourseController::class, 'store'])->middleware('admin');
        Route::put('/{course}', [CourseController::class, 'update'])->middleware('admin');
        Route::delete('/{course}', [CourseController::class, 'destroy'])->middleware('admin');
    });

    // ==================== VÍDEOS ====================
    Route::prefix('videos')->group(function () {
        Route::get('/{video}', [VideoController::class, 'show']);
        Route::post('/{video}/progress', [VideoController::class, 'updateProgress']);
        
        // ADMIN
        Route::post('/', [VideoController::class, 'store'])->middleware('admin');
        Route::put('/{video}', [VideoController::class, 'update'])->middleware('admin');
        Route::delete('/{video}', [VideoController::class, 'destroy'])->middleware('admin');
    });

    // ==================== PROGRESSO ====================
    Route::prefix('progress')->group(function () {
        Route::get('/courses/{courseId}', [VideoController::class, 'courseProgress']);
    });

    // ==================== REPAROS (Diferencial!) ====================
    Route::prefix('repairs')->group(function () {
        Route::get('/', [RepairController::class, 'index']);
        Route::get('/{repair}', [RepairController::class, 'show']);
        Route::post('/', [RepairController::class, 'store']);
        Route::put('/{repair}', [RepairController::class, 'update']);
        Route::delete('/{repair}', [RepairController::class, 'destroy']);
        
        // Fotos
        Route::post('/{repair}/photos', [RepairController::class, 'uploadPhoto']);
        
        // Enviar para análise
        Route::post('/{repair}/submit', [RepairController::class, 'submitForReview']);
        
        // ADMIN - Aprovar/Rejeitar
        Route::post('/{repair}/approve', [RepairController::class, 'approve'])->middleware('admin');
    });

    // ==================== COMUNIDADE - POSTS ====================
    Route::prefix('posts')->group(function () {
        Route::get('/course/{courseId}', [PostController::class, 'indexByCourse']);
        Route::get('/{post}', [PostController::class, 'show']);
        Route::post('/', [PostController::class, 'store']);
        Route::put('/{post}', [PostController::class, 'update']);
        Route::delete('/{post}', [PostController::class, 'destroy']);
        
        // Likes
        Route::post('/{post}/like', [PostController::class, 'like']);
        Route::delete('/{post}/like', [PostController::class, 'unlike']);
        
        // ADMIN - Fixar/Desafixar
        Route::post('/{post}/pin', [PostController::class, 'pin'])->middleware('admin');
        Route::delete('/{post}/pin', [PostController::class, 'unpin'])->middleware('admin');
    });

    // ==================== COMUNIDADE - COMENTÁRIOS ====================
    Route::prefix('comments')->group(function () {
        Route::get('/{comment}', [CommentController::class, 'show']);
        Route::get('/post/{postId}', [CommentController::class, 'indexByPost']);
        Route::post('/', [CommentController::class, 'store']);
        Route::put('/{comment}', [CommentController::class, 'update']);
        Route::delete('/{comment}', [CommentController::class, 'destroy']);
        
        // Likes
        Route::post('/{comment}/like', [CommentController::class, 'like']);
        Route::delete('/{comment}/like', [CommentController::class, 'unlike']);
    });

    // ==================== CERTIFICADOS ====================
    Route::prefix('certificates')->group(function () {
        Route::get('/', [CertificateController::class, 'index']);
        Route::get('/{certificate}', [CertificateController::class, 'show']);
        Route::post('/', [CertificateController::class, 'store']);
        Route::get('/{certificate}/download', [CertificateController::class, 'download']);
        
        // ADMIN
        Route::post('/admin/create', [CertificateController::class, 'adminCreate'])->middleware('admin');
    });

    // ==================== STREAMING SEGURO DE VÍDEO ====================
    Route::get('/videos/{id}/stream-url', [VideoStreamController::class, 'getStreamUrl']);

    // ==================== TRANSMISSÕES AO VIVO (autenticadas) ====================
    Route::prefix('live-streams')->group(function () {
        Route::post('/{id}/viewers', [LiveStreamController::class, 'updateViewers']);

        // Professores e admin
        Route::post('/', [LiveStreamController::class, 'store']);
        Route::put('/{id}', [LiveStreamController::class, 'update']);
        Route::post('/{id}/start', [LiveStreamController::class, 'start']);
        Route::post('/{id}/end', [LiveStreamController::class, 'end']);
        Route::post('/{id}/archive', [LiveStreamController::class, 'archive']);
        Route::delete('/{id}', [LiveStreamController::class, 'destroy']);
    });

    // ==================== ADMIN - SEGURANÇA DE VÍDEO ====================
    Route::middleware('admin')->group(function () {
        Route::get('/admin/videos/{id}/access-log', [VideoStreamController::class, 'accessLog']);
        Route::post('/admin/users/{id}/block-stream', [VideoStreamController::class, 'blockUser']);
    });

});

/*
|--------------------------------------------------------------------------
| Rotas Health Check
|--------------------------------------------------------------------------
*/
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API está funcionando corretamente',
        'timestamp' => now(),
    ]);
});
