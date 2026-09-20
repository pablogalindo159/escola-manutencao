@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-white">
    <!-- Nav -->
    <nav class="border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <span class="text-2xl">🔧</span>
                <span class="text-xl font-bold text-blue-600">Escola da Manutenção</span>
            </a>
            <a href="{{ url('/') }}" class="text-gray-700 hover:text-blue-600">← Voltar aos cursos</a>
        </div>
    </nav>

    <!-- Hero do curso -->
    <div class="bg-gradient-to-br from-blue-600 to-blue-800 text-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <span class="inline-block bg-blue-500/30 text-blue-50 text-sm px-3 py-1 rounded-full mb-4">
                {{ $course->category ?? 'Curso' }}
            </span>
            <h1 class="text-4xl font-bold mb-4">{{ $course->title }}</h1>
            <p class="text-lg text-blue-100 mb-6">{{ $course->description }}</p>
            <div class="flex flex-wrap gap-6 text-sm">
                <div>⭐ {{ number_format($course->rating, 1) }} avaliação</div>
                <div>⏱️ {{ round($course->duration_minutes / 60) }} horas de conteúdo</div>
                <div>📈 Nível: {{ ucfirst($course->level) }}</div>
            </div>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="md:col-span-2">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Sobre o curso</h2>
            <p class="text-gray-700 leading-relaxed">{{ $course->description }}</p>
        </div>

        <div>
            <div class="border rounded-xl p-6 shadow-sm sticky top-6">
                <div class="text-3xl font-bold text-blue-600 mb-4">
                    R$ {{ number_format($course->price, 2, ',', '.') }}
                </div>

                @auth
                    @if ($isSubscribed)
                        <a href="{{ route('student.courses.show', $course) }}"
                           class="block text-center w-full py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
                            ✅ Ir para o curso
                        </a>
                    @elseif ($course->type === 'free')
                        <form method="POST" action="{{ route('student.courses.enroll', $course) }}">
                            @csrf
                            <button type="submit"
                                class="w-full py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                                Inscrever-se Grátis
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('student.courses.checkout', $course) }}">
                            @csrf
                            <button type="submit"
                                class="w-full py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                                Comprar com Mercado Pago
                            </button>
                        </form>
                        <a href="https://wa.me/554132830558" target="_blank"
                           class="block text-center w-full py-2.5 mt-2 text-blue-600 text-sm font-medium hover:underline">
                            Prefere falar com a gente primeiro?
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}"
                       class="block text-center w-full py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                        Fazer login para se inscrever
                    </a>
                    <p class="text-xs text-gray-500 mt-3 text-center">
                        Não tem conta? <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Cadastre-se</a>
                    </p>
                @endauth
            </div>
        </div>
    </div>
</div>
@endsection
