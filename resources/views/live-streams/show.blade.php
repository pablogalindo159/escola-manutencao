@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
<!-- live-stream-player.blade.php -->
<div class="live-stream-container bg-black rounded-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-red-600 to-red-800 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
            <h1 class="text-white font-bold text-xl">
                @if($stream->isLive())
                    AO VIVO AGORA
                @else
                    TRANSMISSÃO AGENDADA
                @endif
            </h1>
        </div>
        <p class="text-red-200 text-sm">
            {{ $stream->scheduled_at->format('d/m/Y H:i') }}
        </p>
    </div>

    <!-- Player YouTube -->
    <div class="bg-black aspect-video flex items-center justify-center">
        @if($stream->youtube_video_id)
            <iframe
                width="100%"
                height="100%"
                src="{{ $stream->getEmbedUrl() }}"
                title="{{ $stream->title }}"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen>
            </iframe>
        @else
            <div class="text-center text-gray-400">
                <p class="text-lg mb-4">YouTube Video ID não configurado</p>
                @auth
                    @if(auth()->user()->id === $stream->user_id || auth()->user()->role === 'admin')
                        <a href="{{ route('admin.live-streams.edit', $stream) }}" 
                           class="inline-block bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                            Configurar Video ID
                        </a>
                    @endif
                @endauth
            </div>
        @endif
    </div>

    <!-- Info Section -->
    <div class="bg-gray-900 px-6 py-4">
        <!-- Título e Descrição -->
        <div class="mb-6">
            <h2 class="text-white text-2xl font-bold mb-2">
                {{ $stream->title }}
            </h2>
            <p class="text-gray-300 text-base leading-relaxed">
                {{ $stream->description }}
            </p>
        </div>

        <!-- Meta Info -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 pb-6 border-b border-gray-700">
            <!-- Professor -->
            <div>
                <p class="text-gray-400 text-sm">Professor</p>
                <p class="text-white font-semibold">
                    {{ $stream->user->name }}
                </p>
            </div>

            <!-- Visualizadores -->
            <div>
                <p class="text-gray-400 text-sm">Visualizadores</p>
                <p class="text-white font-semibold">
                    @if($stream->isLive())
                        <span class="text-red-500">{{ number_format($stream->viewers_count) }}</span> ao vivo
                    @else
                        {{ number_format($stream->total_viewers) }} assistiram
                    @endif
                </p>
            </div>

            <!-- Status -->
            <div>
                <p class="text-gray-400 text-sm">Status</p>
                <div class="flex items-center gap-2">
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
                        @if($stream->status === 'live')
                            bg-red-600 text-white
                        @elseif($stream->status === 'scheduled')
                            bg-yellow-600 text-white
                        @elseif($stream->status === 'archived')
                            bg-green-600 text-white
                        @else
                            bg-gray-600 text-white
                        @endif
                    ">
                        {{ ucfirst($stream->status) }}
                    </span>
                </div>
            </div>

            <!-- Curtidas -->
            <div>
                <p class="text-gray-400 text-sm">Curtidas</p>
                <p class="text-white font-semibold flex items-center gap-2">
                    ❤️ {{ number_format($stream->likes) }}
                </p>
            </div>
        </div>

        <!-- Links -->
        <div class="flex gap-3">
            <!-- Link Direto YouTube -->
            @if($stream->youtube_video_id)
                <a href="{{ $stream->getYoutubeUrl() }}" 
                   target="_blank"
                   class="inline-flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.012 8.012 0 0 0 16 8c0-4.42-3.58-8-8-8z"/>
                    </svg>
                    Assistir no YouTube
                </a>
            @endif

            <!-- Admin Actions -->
            @auth
                @if(auth()->user()->id === $stream->user_id || auth()->user()->role === 'admin')
                    <button onclick="copyLink('{{ $stream->getYoutubeUrl() }}')" 
                            class="inline-flex items-center gap-2 bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Copiar Link
                    </button>

                    <a href="{{ route('admin.live-streams.edit', $stream) }}" 
                       class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Editar
                    </a>
                @endif
            @endauth
        </div>
    </div>

    <!-- Chat Section -->
    @if($stream->allow_chat && $stream->youtube_video_id)
        <div class="bg-gray-800 border-t border-gray-700 px-6 py-4">
            <h3 class="text-white font-bold mb-3">Chat ao Vivo (YouTube)</h3>
            <p class="text-gray-400 text-sm mb-3">
                Comente e interaja diretamente no YouTube. 
                <a href="{{ $stream->getYoutubeUrl() }}" target="_blank" class="text-blue-500 hover:underline">
                    Abra a transmissão →
                </a>
            </p>
        </div>
    @endif
</div>

<!-- CSS customizado -->
<style>
    .live-stream-container {
        box-shadow: 0 0 20px rgba(239, 68, 68, 0.3);
    }

    .live-stream-container:hover {
        box-shadow: 0 0 30px rgba(239, 68, 68, 0.5);
    }
</style>

<!-- Script para copiar link -->
<script>
    function copyLink(url) {
        navigator.clipboard.writeText(url).then(() => {
            alert('Link copiado! 📋');
        }).catch(err => {
            console.error('Erro ao copiar:', err);
        });
    }
</script>
</div>
@endsection
