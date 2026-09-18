@extends('layouts.student')

@section('title', $video->title)

@section('content')
<a href="{{ route('student.courses.show', $video->course_id) }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">← {{ $video->course->title }}</a>

<div class="bg-black rounded-xl overflow-hidden mb-4" style="aspect-ratio: 16/9;">
    @if ($video->youtube_id)
        <iframe
            class="w-full h-full"
            src="https://www.youtube.com/embed/{{ $video->youtube_id }}?modestbranding=1&rel=0&iv_load_policy=3&fs=1"
            title="{{ $video->title }}"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
        ></iframe>
    @else
        <video
            controls
            controlsList="nodownload"
            oncontextmenu="return false;"
            class="w-full h-full"
            poster="{{ $video->thumbnail_url }}"
        >
            <source src="{{ route('student.videos.stream', $video) }}" type="video/mp4">
            Seu navegador não suporta reprodução de vídeo.
        </video>
    @endif
</div>

<div class="bg-white rounded-xl shadow p-6 mb-4">
    <div class="flex items-start justify-between gap-4 mb-3">
        <h1 class="text-xl font-bold text-gray-900">{{ $video->title }}</h1>

        @if ($progress && $progress->is_completed)
            <span class="shrink-0 px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-sm font-medium">✅ Concluída</span>
        @else
            <form method="POST" action="{{ route('student.videos.complete', $video) }}" class="shrink-0">
                @csrf
                <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                    Marcar como concluída
                </button>
            </form>
        @endif
    </div>

    @if ($video->description)
        <p class="text-gray-600 text-sm mb-4">{{ $video->description }}</p>
    @endif

    @if ($video->material_url)
        <a href="{{ $video->material_url }}" target="_blank" class="inline-flex items-center gap-2 text-blue-600 hover:underline text-sm font-medium">
            📄 Material de apoio
        </a>
    @endif
</div>

<div class="flex items-center justify-between">
    @if ($previousVideo)
        <a href="{{ route('student.videos.watch', $previousVideo) }}" class="text-blue-600 hover:underline text-sm font-medium">← Aula anterior</a>
    @else
        <span></span>
    @endif

    @if ($nextVideo)
        <a href="{{ route('student.videos.watch', $nextVideo) }}" class="text-blue-600 hover:underline text-sm font-medium">Próxima aula →</a>
    @endif
</div>
@endsection
