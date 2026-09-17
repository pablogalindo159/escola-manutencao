<?php

use App\Http\Controllers\Api\{
    AuthController,
    CourseController,
    VideoController,
    RepairController,
    PostController,
    CommentController,
    CertificateController,
};
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

// Rotas de cursos públicas
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/featured', [CourseController::class, 'featured']);
Route::get('/courses/{course}', [CourseController::class, 'show']);

// Verificar certificado público
Route::get('/certificates/verify/{number}', [CertificateController::class, 'verify']);

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
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });

    // ==================== CURSOS ====================
    Route::prefix('courses')->group(function () {
        Route::get('/my-courses', [CourseController::class, 'myCourses']);
        Route::post('/{course}/subscribe', [CourseController::class, 'subscribe']);
        
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
