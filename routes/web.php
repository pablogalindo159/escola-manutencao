<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\LiveStreamController as AdminLiveStreamController;
use App\Http\Controllers\Admin\VideoController as AdminVideoController;
use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\CourseController;
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

Route::get('/cursos/{course}', [CourseController::class, 'show'])->name('courses.detail');

Route::get('/live-streams/{liveStream}', function (LiveStream $liveStream) {
    return view('live-streams.show', ['stream' => $liveStream]);
})->name('live-streams.show');

Route::middleware(['auth', 'admin.web'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/admin/cursos', [AdminDashboardController::class, 'courses'])->name('admin.courses');
    Route::get('/admin/cursos/novo', [AdminDashboardController::class, 'createCourse'])->name('admin.courses.create');
    Route::post('/admin/cursos', [AdminDashboardController::class, 'storeCourse'])->name('admin.courses.store');
    Route::get('/admin/cursos/{course}/editar', [AdminDashboardController::class, 'editCourse'])->name('admin.courses.edit');
    Route::put('/admin/cursos/{course}', [AdminDashboardController::class, 'updateCourse'])->name('admin.courses.update');

    Route::prefix('admin/cursos/{course}/videos')->name('admin.videos.')->group(function () {
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
