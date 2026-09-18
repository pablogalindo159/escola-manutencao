<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\LiveStreamController as AdminLiveStreamController;
use App\Http\Controllers\Admin\VideoController as AdminVideoController;
use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\CourseController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\VideoController as StudentVideoController;
use App\Http\Controllers\Student\CommunityController as StudentCommunityController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Models\Course;
use App\Models\LiveStream;

Route::get('/', function () {
    $style_by_category = [
        'Notebooks' => ['gradient' => 'bg-gradient-to-br from-blue-500 to-blue-700', 'icon' => '💻'],
        'Smartphones' => ['gradient' => 'bg-gradient-to-br from-purple-500 to-purple-700', 'icon' => '📱'],
        'Eletrônica' => ['gradient' => 'bg-gradient-to-br from-green-500 to-green-700', 'icon' => '🔧'],
    ];

    $featured_courses = Course::where('status', 'published')
        ->where('featured', true)
        ->take(6)
        ->get()
        ->map(function ($course) use ($style_by_category) {
            $style = $style_by_category[$course->category] ?? ['gradient' => 'bg-gradient-to-br from-gray-500 to-gray-700', 'icon' => '📘'];

            return [
                'id' => $course->id,
                'name' => $course->title,
                'gradient' => $style['gradient'],
                'icon' => $style['icon'],
                'reviews' => $course->subscribers()->count(),
                'hours' => round($course->duration_minutes / 60),
                'videos' => $course->videos()->count(),
                'price' => $course->price,
            ];
        });

    $testimonials = [
        [
            'name' => 'Carlos Silva',
            'course' => 'Manutenção de Notebooks',
            'text' => 'Curso excelente, me ajudou a abrir minha própria assistência técnica.',
        ],
        [
            'name' => 'Ana Paula',
            'course' => 'Reparo de Smartphones',
            'text' => 'Didática muito boa, professores explicam com calma cada passo.',
        ],
    ];

    return view('landing', compact('featured_courses', 'testimonials'));
});

Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

Route::get('/cadastro', [WebAuthController::class, 'showRegister'])->name('register');
Route::post('/cadastro', [WebAuthController::class, 'register'])->name('register.attempt');

Route::get('/cursos/{course}', [CourseController::class, 'show'])->name('courses.detail');

Route::get('/live-streams/{liveStream}', function (LiveStream $liveStream) {
    return view('live-streams.show', ['stream' => $liveStream]);
})->name('live-streams.show');

Route::middleware('auth')->prefix('minha-area')->name('student.')->group(function () {
    Route::get('/', [StudentDashboardController::class, 'index'])->name('dashboard');

    Route::get('/cursos/{course}', [StudentCourseController::class, 'show'])->name('courses.show');
    Route::post('/cursos/{course}/inscrever', [StudentCourseController::class, 'enroll'])->name('courses.enroll');

    Route::get('/videos/{video}', [StudentVideoController::class, 'watch'])->name('videos.watch');
    Route::get('/videos/{video}/stream', [StudentVideoController::class, 'stream'])->name('videos.stream');
    Route::post('/videos/{video}/concluir', [StudentVideoController::class, 'markComplete'])->name('videos.complete');

    Route::get('/comunidade', [StudentCommunityController::class, 'index'])->name('community.index');
    Route::get('/comunidade/novo', [StudentCommunityController::class, 'create'])->name('community.create');
    Route::post('/comunidade', [StudentCommunityController::class, 'store'])->name('community.store');
    Route::get('/comunidade/{post}', [StudentCommunityController::class, 'show'])->name('community.show');
    Route::post('/comunidade/{post}/comentarios', [StudentCommunityController::class, 'comment'])->name('community.comment');
    Route::post('/comunidade/{post}/curtir', [StudentCommunityController::class, 'like'])->name('community.like');
    Route::delete('/comunidade/{post}/curtir', [StudentCommunityController::class, 'unlike'])->name('community.unlike');

    Route::get('/perfil', [StudentProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/perfil', [StudentProfileController::class, 'update'])->name('profile.update');
    Route::put('/perfil/senha', [StudentProfileController::class, 'updatePassword'])->name('profile.password');
});

Route::middleware(['auth', 'admin.web'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/admin/cursos', [AdminDashboardController::class, 'courses'])->name('admin.courses');
    Route::get('/admin/cursos/novo', [AdminDashboardController::class, 'createCourse'])->name('admin.courses.create');
    Route::post('/admin/cursos', [AdminDashboardController::class, 'storeCourse'])->name('admin.courses.store');
    Route::get('/admin/cursos/{course}/editar', [AdminDashboardController::class, 'editCourse'])->name('admin.courses.edit');
    Route::put('/admin/cursos/{course}', [AdminDashboardController::class, 'updateCourse'])->name('admin.courses.update');
    Route::post('/admin/cursos/{course}/matricular', [AdminDashboardController::class, 'enrollStudent'])->name('admin.courses.enroll');

    Route::prefix('admin/cursos/{course}/videos')->name('admin.videos.')->group(function () {
        Route::post('/detectar', [AdminVideoController::class, 'detectMetadata'])->name('detect');
        Route::get('/', [AdminVideoController::class, 'index'])->name('index');
        Route::get('/novo', [AdminVideoController::class, 'create'])->name('create');
        Route::post('/', [AdminVideoController::class, 'store'])->name('store');
        Route::get('/{video}/editar', [AdminVideoController::class, 'edit'])->name('edit');
        Route::put('/{video}', [AdminVideoController::class, 'update'])->name('update');
        Route::delete('/{video}', [AdminVideoController::class, 'destroy'])->name('destroy');
    });

    Route::get('/admin/pagamentos', [AdminDashboardController::class, 'payments'])->name('admin.payments');

    Route::prefix('admin/live-streams')->name('admin.live-streams.')->group(function () {
        Route::get('/', [AdminLiveStreamController::class, 'index'])->name('index');
        Route::get('/create', [AdminLiveStreamController::class, 'create'])->name('create');
        Route::post('/', [AdminLiveStreamController::class, 'store'])->name('store');
        Route::get('/{liveStream}/edit', [AdminLiveStreamController::class, 'edit'])->name('edit');
        Route::put('/{liveStream}', [AdminLiveStreamController::class, 'update'])->name('update');
        Route::delete('/{liveStream}', [AdminLiveStreamController::class, 'destroy'])->name('destroy');
        Route::post('/{liveStream}/start', [AdminLiveStreamController::class, 'start'])->name('start');
        Route::post('/{liveStream}/end', [AdminLiveStreamController::class, 'end'])->name('end');
    });
});
