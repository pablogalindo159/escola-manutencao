@extends('layouts.student')

@section('title', 'Meus Cursos')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Olá, {{ auth()->user()->name }}! 👋</h1>

@if ($courses->isEmpty())
    <div class="bg-white rounded-xl shadow p-10 text-center">
        <p class="text-gray-600 mb-4">Você ainda não está inscrito em nenhum curso.</p>
        <a href="{{ url('/#cursos') }}" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">
            Ver cursos disponíveis
        </a>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach ($courses as $course)
            <a href="{{ route('student.courses.show', $course) }}" class="bg-white rounded-xl shadow hover:shadow-md transition overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded-full">{{ $course->category }}</span>
                        <span class="text-xs text-gray-500">{{ $progressByCourse[$course->id] ?? 0 }}% concluído</span>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900 mb-2">{{ $course->title }}</h2>
                    <div class="w-full bg-gray-200 rounded-full h-2 mb-1">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $progressByCourse[$course->id] ?? 0 }}%"></div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
