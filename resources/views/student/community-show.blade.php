@extends('layouts.student')

@section('title', $post->title)

@section('content')
<a href="{{ route('student.community.index') }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">← Comunidade</a>

<div class="bg-white rounded-xl shadow p-6 mb-4">
    <div class="flex items-center gap-2 mb-3">
        @if ($post->is_pinned)
            <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-medium">📌 Fixado</span>
        @endif
        <span class="text-xs text-gray-500">{{ $post->author->name ?? 'Usuário' }} · {{ $post->created_at->diffForHumans() }}</span>
    </div>

    <h1 class="text-xl font-bold text-gray-900 mb-3">{{ $post->title }}</h1>
    <p class="text-gray-700 whitespace-pre-line mb-4">{{ $post->content }}</p>

    <div class="flex items-center gap-3 pt-3 border-t">
        @php
            $liked = $post->likes()->where('user_id', auth()->id())->exists();
        @endphp
        <form method="POST" action="{{ route($liked ? 'student.community.unlike' : 'student.community.like', $post) }}">
            @csrf
            @if ($liked) @method('DELETE') @endif
            <button type="submit" class="text-sm font-medium {{ $liked ? 'text-red-600' : 'text-gray-600' }} hover:text-red-600">
                {{ $liked ? '❤️' : '🤍' }} {{ $post->likes_count }} {{ $post->likes_count === 1 ? 'curtida' : 'curtidas' }}
            </button>
        </form>
        <span class="text-sm text-gray-500">💬 {{ $post->comments->count() }} comentários</span>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-6 mb-4">
    <h2 class="font-bold text-gray-900 mb-4">Comentar</h2>
    <form method="POST" action="{{ route('student.community.comment', $post) }}" class="space-y-3">
        @csrf
        <textarea name="content" rows="3" required placeholder="Escreva um comentário..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700">
                Comentar
            </button>
        </div>
    </form>
</div>

<div class="space-y-3">
    @forelse ($post->comments as $comment)
        <div class="bg-white rounded-xl shadow p-4">
            <div class="flex items-center gap-2 mb-1">
                <span class="font-medium text-gray-900 text-sm">{{ $comment->author->name ?? 'Usuário' }}</span>
                <span class="text-xs text-gray-500">· {{ $comment->created_at->diffForHumans() }}</span>
            </div>
            <p class="text-gray-700 text-sm whitespace-pre-line">{{ $comment->content }}</p>
        </div>
    @empty
        <p class="text-center text-gray-500 text-sm py-6">Nenhum comentário ainda.</p>
    @endforelse
</div>
@endsection
