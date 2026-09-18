<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Web\WebAuthController;

Route::get('/', function () {
    $featured_courses = [
        [
            'id' => 1,
            'name' => 'Manutenção de Notebooks',
            'gradient' => 'bg-gradient-to-br from-blue-500 to-blue-700',
            'icon' => '💻',
            'reviews' => 128,
            'hours' => 24,
            'videos' => 45,
            'price' => 297.00,
        ],
        [
            'id' => 2,
            'name' => 'Reparo de Smartphones',
            'gradient' => 'bg-gradient-to-br from-purple-500 to-purple-700',
            'icon' => '📱',
            'reviews' => 96,
            'hours' => 18,
            'videos' => 32,
            'price' => 247.00,
        ],
        [
            'id' => 3,
            'name' => 'Diagnóstico Eletrônico',
            'gradient' => 'bg-gradient-to-br from-green-500 to-green-700',
            'icon' => '🔧',
            'reviews' => 74,
            'hours' => 30,
            'videos' => 52,
            'price' => 347.00,
        ],
    ];

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

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
});
