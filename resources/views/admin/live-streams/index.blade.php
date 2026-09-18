@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-3xl font-bold text-gray-900">Transmissões ao Vivo</h1>
            <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">← Voltar ao Dashboard</a>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex items-center justify-between mb-6">
            <p class="text-gray-600">Gerencie suas transmissões via YouTube Live</p>
            <a href="{{ route('admin.live-streams.create') }}"
               class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition font-semibold">
                + Nova Transmissão
            </a>
        </div>

        <!-- Tabs -->
        <div class="mb-8 border-b border-gray-200">
            <div class="flex gap-8">
                <a href="?status=scheduled"
                   class="pb-4 px-4 font-semibold border-b-2 @if(!request('status') || request('status') === 'scheduled') border-red-600 text-gray-900 @else border-transparent text-gray-600 @endif">
                    📅 Agendadas
                </a>
                <a href="?status=live"
                   class="pb-4 px-4 font-semibold border-b-2 @if(request('status') === 'live') border-red-600 text-gray-900 @else border-transparent text-gray-600 @endif">
                    🔴 Ao Vivo
                </a>
                <a href="?status=archived"
                   class="pb-4 px-4 font-semibold border-b-2 @if(request('status') === 'archived') border-red-600 text-gray-900 @else border-transparent text-gray-600 @endif">
                    ✅ Arquivadas
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-semibold">Próximas Transmissões</p>
                <p class="text-4xl font-bold text-gray-900 mt-2">{{ $stats['upcoming'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border-t-4 border-red-600">
                <p class="text-gray-600 text-sm font-semibold">Ao Vivo Agora</p>
                <p class="text-4xl font-bold text-red-600 mt-2">{{ $stats['live'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-semibold">Total Visualizadores</p>
                <p class="text-4xl font-bold text-gray-900 mt-2">{{ number_format($stats['total_viewers']) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-semibold">Arquivadas</p>
                <p class="text-4xl font-bold text-gray-900 mt-2">{{ $stats['archived'] }}</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            @if($streams->count())
                <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-4 text-left font-semibold text-gray-900">Título</th>
                            <th class="px-6 py-4 text-left font-semibold text-gray-900">Professor</th>
                            <th class="px-6 py-4 text-left font-semibold text-gray-900">Data/Hora</th>
                            <th class="px-6 py-4 text-left font-semibold text-gray-900">Status</th>
                            <th class="px-6 py-4 text-left font-semibold text-gray-900">Visualizadores</th>
                            <th class="px-6 py-4 text-right font-semibold text-gray-900">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($streams as $stream)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900">{{ $stream->title }}</p>
                                    <p class="text-sm text-gray-600 mt-1">{{ \Illuminate\Support\Str::limit($stream->description, 50) }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-gray-900">{{ $stream->user->name }}</p>
                                    <p class="text-sm text-gray-600">{{ $stream->user->email }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-gray-900">{{ $stream->scheduled_at->format('d/m/Y') }}</p>
                                    <p class="text-sm text-gray-600">{{ $stream->scheduled_at->format('H:i') }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusColors = [
                                            'live' => 'bg-red-100 text-red-800',
                                            'scheduled' => 'bg-yellow-100 text-yellow-800',
                                            'archived' => 'bg-green-100 text-green-800',
                                            'ended' => 'bg-gray-100 text-gray-800',
                                        ];
                                        $statusLabels = [
                                            'live' => '🔴 Ao Vivo',
                                            'scheduled' => '📅 Agendada',
                                            'archived' => '✅ Arquivada',
                                            'ended' => '⏸️ Finalizada',
                                        ];
                                    @endphp
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold {{ $statusColors[$stream->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $statusLabels[$stream->status] ?? $stream->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-gray-900 font-semibold">
                                        @if($stream->status === 'live')
                                            <span class="text-red-600">{{ number_format($stream->viewers_count) }}</span> agora
                                        @else
                                            {{ number_format($stream->total_viewers) }} total
                                        @endif
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('live-streams.show', $stream) }}"
                                           title="Visualizar"
                                           class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition">
                                            👁️
                                        </a>

                                        <a href="{{ route('admin.live-streams.edit', $stream) }}"
                                           title="Editar"
                                           class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition">
                                            ✏️
                                        </a>

                                        @if($stream->status === 'scheduled')
                                            <form action="{{ route('admin.live-streams.start', $stream) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" title="Iniciar"
                                                        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition">
                                                    🔴
                                                </button>
                                            </form>
                                        @elseif($stream->status === 'live')
                                            <form action="{{ route('admin.live-streams.end', $stream) }}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" title="Finalizar"
                                                        class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-orange-100 text-orange-600 hover:bg-orange-200 transition">
                                                    ⏹️
                                                </button>
                                            </form>
                                        @endif

                                        <button onclick="if(confirm('Tem certeza?')) { document.getElementById('delete-form-{{ $stream->id }}').submit(); }"
                                                title="Deletar"
                                                class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition">
                                            🗑️
                                        </button>
                                        <form id="delete-form-{{ $stream->id }}"
                                              action="{{ route('admin.live-streams.destroy', $stream) }}"
                                              method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>

                <div class="px-6 py-4 border-t bg-gray-50">
                    {{ $streams->links() }}
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <p class="text-gray-600 text-lg mb-4">Nenhuma transmissão encontrada</p>
                    <a href="{{ route('admin.live-streams.create') }}"
                       class="inline-block bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                        Criar Primeira Transmissão
                    </a>
                </div>
            @endif
        </div>

        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-bold text-blue-900 mb-4">📚 Como usar YouTube Live</h3>
            <ol class="list-decimal list-inside space-y-2 text-blue-900 text-sm">
                <li>Agende uma transmissão aqui</li>
                <li>No YouTube Studio, crie uma nova transmissão ao vivo</li>
                <li>Copie o <strong>Video ID</strong> do link (ex: youtube.com/watch?v=<strong>AQUI_VEM_O_ID</strong>)</li>
                <li>Cole o Video ID ao editar a transmissão</li>
                <li>Quando começar a transmitir no YouTube, clique em "Iniciar" aqui</li>
                <li>Seus alunos verão o player da transmissão na plataforma</li>
            </ol>
        </div>
    </div>
</div>
@endsection
