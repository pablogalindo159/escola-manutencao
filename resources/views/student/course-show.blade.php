@extends('layouts.student')

@section('title', $course->title)

@section('content')
<a href="{{ route('student.dashboard') }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">← Meus Cursos</a>

<div class="bg-white rounded-xl shadow p-6 mb-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $course->title }}</h1>
    <p class="text-gray-600">{{ $course->description }}</p>
</div>

<h2 class="text-lg font-bold text-gray-900 mb-3">Aulas</h2>

<div class="bg-white rounded-xl shadow overflow-hidden divide-y divide-gray-200">
    @forelse ($videos as $video)
        <a href="{{ route('student.videos.watch', $video) }}" class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition">
            <div class="flex items-center gap-3 min-w-0">
                @if (in_array($video->id, $completedVideoIds))
                    <span class="text-green-600 text-lg shrink-0">✅</span>
                @else
                    <span class="text-gray-300 text-lg shrink-0">▶️</span>
                @endif
                <div class="min-w-0">
                    <p class="font-medium text-gray-900 truncate">{{ $video->title }}</p>
                    <p class="text-xs text-gray-500">
                        {{ sprintf('%d:%02d', intdiv($video->duration_seconds, 60), $video->duration_seconds % 60) }}
                    </p>
                </div>
            </div>
            <span class="text-gray-400 text-sm shrink-0">Assistir →</span>
        </a>
    @empty
        <p class="px-5 py-8 text-center text-gray-500 text-sm">Nenhuma aula publicada ainda. Volte em breve!</p>
    @endforelse
</div>
@endsection
