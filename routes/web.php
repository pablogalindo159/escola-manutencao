<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\CourseController;
use App\Models\Course;

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

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
});
