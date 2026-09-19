@extends('layouts.student')

@section('title', 'Todos os Cursos')

@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-6">Todos os Cursos</h1>

@if ($courses->isEmpty())
    <div class="bg-white rounded-xl shadow p-10 text-center text-gray-500">
        Nenhum curso publicado no momento. Volte em breve!
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach ($courses as $course)
            @php $isSubscribed = in_array($course->id, $subscribedCourseIds); @endphp
            <a href="{{ $isSubscribed ? route('student.courses.show', $course) : route('courses.detail', $course) }}"
               class="bg-white rounded-xl shadow hover:shadow-md transition overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded-full">{{ $course->category ?? 'Curso' }}</span>
                        @if ($isSubscribed)
                            <span class="text-xs font-semibold text-green-700 bg-green-50 px-2 py-1 rounded-full">✅ Já é seu</span>
                        @elseif ($course->type === 'free')
                            <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-1 rounded-full">Grátis</span>
                        @else
                            <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-1 rounded-full">
                                R$ {{ number_format($course->price, 2, ',', '.') }}
                            </span>
                        @endif
                    </div>
                    <h2 class="text-lg font-bold text-gray-900 mb-2">{{ $course->title }}</h2>
                    <p class="text-sm text-gray-600 line-clamp-2">{{ $course->description }}</p>
                    <div class="flex items-center gap-2 mt-3 text-xs text-gray-500">
                        @if ($course->instructor)
                            <span>{{ $course->instructor->name }}</span>
                            <span>·</span>
                        @endif
                        <span>{{ ucfirst($course->level) }}</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
