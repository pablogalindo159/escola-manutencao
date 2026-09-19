@extends('layouts.student')

@section('title', 'Comunidade')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Comunidade</h1>
    <a href="{{ route('student.community.create') }}{{ $selectedCourseId ? '?course_id='.$selectedCourseId : '' }}"
       class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700">
        + Novo Post
    </a>
</div>

@if ($courses->count() > 0)
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('student.community.index') }}"
           class="px-3 py-1.5 rounded-full text-sm font-medium {{ !$selectedCourseId ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border' }}">
            Todos os cursos
        </a>
        @foreach ($courses as $course)
            <a href="{{ route('student.community.index', ['course_id' => $course->id]) }}"
               class="px-3 py-1.5 rounded-full text-sm font-medium {{ (string) $selectedCourseId === (string) $course->id ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border' }}">
                {{ $course->title }}
            </a>
        @endforeach
    </div>
@endif

<div class="space-y-4">
    @forelse ($posts as $post)
        <a href="{{ route('student.community.show', $post) }}" class="block bg-white rounded-xl shadow p-5 hover:shadow-md transition">
            <div class="flex items-center gap-2 mb-2">
                @if ($post->is_pinned)
                    <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-medium">📌 Fixado</span>
                @endif
                <span class="text-xs text-gray-500">{{ $post->author->name ?? 'Usuário' }} · {{ $post->created_at->diffForHumans() }}</span>
            </div>
            <h2 class="font-bold text-gray-900 mb-1">{{ $post->title }}</h2>
            <p class="text-gray-600 text-sm mb-3">{{ \Illuminate\Support\Str::limit($post->content, 150) }}</p>
            <div class="flex items-center gap-4 text-xs text-gray-500">
                <span>❤️ {{ $post->likes_count }}</span>
                <span>💬 {{ $post->comments_count }}</span>
            </div>
        </a>
    @empty
        <div class="bg-white rounded-xl shadow p-10 text-center text-gray-500">
            Nenhum post ainda. Seja o primeiro a publicar!
        </div>
    @endforelse
</div>

<div class="mt-6">
    {{ $posts->links() }}
</div>
@endsection
