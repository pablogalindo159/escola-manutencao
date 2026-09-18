@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <a href="{{ route('admin.live-streams.index') }}" class="text-blue-600 hover:underline mb-4 inline-block">← Voltar</a>
            <h1 class="text-3xl font-bold text-gray-900">
                {{ isset($stream) ? 'Editar Transmissão' : 'Nova Transmissão ao Vivo' }}
            </h1>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-8">
            <form action="{{ isset($stream) ? route('admin.live-streams.update', $stream) : route('admin.live-streams.store') }}"
                  method="POST">
                @csrf
                @if(isset($stream))
                    @method('PUT')
                @endif

                <div class="mb-6">
                    <label for="title" class="block text-sm font-semibold text-gray-900 mb-2">Título da Transmissão *</label>
                    <input type="text" id="title" name="title"
                           value="{{ old('title', $stream->title ?? '') }}"
                           placeholder="Ex: Live Q&A sobre Manutenção de Ar Condicionado"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                           required>
                </div>

                <div class="mb-6">
                    <label for="description" class="block text-sm font-semibold text-gray-900 mb-2">Descrição</label>
                    <textarea id="description" name="description" rows="4"
                              placeholder="Descreva o tema e objetivos da transmissão..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">{{ old('description', $stream->description ?? '') }}</textarea>
                </div>

                <div class="mb-6">
                    <label for="course_id" class="block text-sm font-semibold text-gray-900 mb-2">Curso Relacionado (opcional)</label>
                    <select id="course_id" name="course_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                        <option value="">— Nenhum curso específico —</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" @selected(old('course_id', $stream->course_id ?? null) == $course->id)>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="scheduled_date" class="block text-sm font-semibold text-gray-900 mb-2">Data *</label>
                        <input type="date" id="scheduled_date" name="scheduled_date"
                               value="{{ old('scheduled_date', isset($stream) ? $stream->scheduled_at->format('Y-m-d') : '') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                               required>
                    </div>
                    <div>
                        <label for="scheduled_time" class="block text-sm font-semibold text-gray-900 mb-2">Horário *</label>
                        <input type="time" id="scheduled_time" name="scheduled_time"
                               value="{{ old('scheduled_time', isset($stream) ? $stream->scheduled_at->format('H:i') : '') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                               required>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="youtube_video_id" class="block text-sm font-semibold text-gray-900 mb-2">
                        YouTube Video ID {{ isset($stream) ? '(opcional)' : '(pode adicionar depois)' }}
                    </label>
                    <input type="text" id="youtube_video_id" name="youtube_video_id"
                           value="{{ old('youtube_video_id', $stream->youtube_video_id ?? '') }}"
                           placeholder="Ex: dQw4w9WgXcQ"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                    <p class="text-gray-600 text-sm mt-2">
                        📌 Como encontrar: YouTube Studio → Transmissões ao vivo → Link compartilhável.
                        Copie apenas a parte após <code class="bg-gray-100 px-2 py-1 rounded">v=</code>
                    </p>
                </div>

                <div class="mb-6 bg-gray-50 rounded-lg p-6 border border-gray-200">
                    <h3 class="font-bold text-gray-900 mb-4">Configurações</h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="allow_chat" name="allow_chat" value="1"
                                   @checked(old('allow_chat', $stream->allow_chat ?? true))
                                   class="w-4 h-4 text-red-600 border-gray-300 rounded">
                            <label for="allow_chat" class="ml-3 text-gray-900">
                                Permitir chat ao vivo <span class="text-gray-600 text-sm">(link para o YouTube)</span>
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="recorded" name="recorded" value="1"
                                   @checked(old('recorded', $stream->recorded ?? true))
                                   class="w-4 h-4 text-red-600 border-gray-300 rounded">
                            <label for="recorded" class="ml-3 text-gray-900">
                                Manter gravação no YouTube <span class="text-gray-600 text-sm">(para assistir depois)</span>
                            </label>
                        </div>
                    </div>
                </div>

                @if(isset($stream))
                    <div class="mb-6">
                        <label for="status" class="block text-sm font-semibold text-gray-900 mb-2">Status</label>
                        <select id="status" name="status"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="scheduled" @selected($stream->status === 'scheduled')>📅 Agendada</option>
                            <option value="live" @selected($stream->status === 'live')>🔴 Ao Vivo</option>
                            <option value="ended" @selected($stream->status === 'ended')>⏸️ Finalizada</option>
                            <option value="archived" @selected($stream->status === 'archived')>✅ Arquivada</option>
                        </select>
                    </div>
                @endif

                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-red-600 text-white font-semibold px-6 py-3 rounded-lg hover:bg-red-700 transition">
                        {{ isset($stream) ? '💾 Salvar Alterações' : '✨ Agendar Transmissão' }}
                    </button>
                    <a href="{{ route('admin.live-streams.index') }}"
                       class="flex-1 bg-gray-300 text-gray-900 font-semibold px-6 py-3 rounded-lg hover:bg-gray-400 transition text-center">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>

        <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <h3 class="font-bold text-yellow-900 mb-3">📖 Passo a passo para transmitir</h3>
            <ol class="list-decimal list-inside space-y-2 text-yellow-900 text-sm">
                <li><strong>Aqui:</strong> agende a transmissão com data, hora e título</li>
                <li><strong>YouTube Studio:</strong> acesse youtube.com/studio</li>
                <li><strong>YouTube:</strong> crie uma "Transmissão ao vivo" com a mesma data/hora</li>
                <li><strong>YouTube:</strong> copie o Video ID</li>
                <li><strong>Aqui:</strong> cole o Video ID no campo acima</li>
                <li><strong>YouTube:</strong> inicie a transmissão</li>
                <li><strong>Aqui:</strong> clique em "Iniciar" pra liberar o player pros alunos</li>
            </ol>
        </div>
    </div>
</div>

<script>
    document.querySelector('form').addEventListener('submit', function(e) {
        const date = document.getElementById('scheduled_date').value;
        const time = document.getElementById('scheduled_time').value;

        if (date && time) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'scheduled_at';
            input.value = `${date} ${time}:00`;
            this.appendChild(input);

            document.getElementById('scheduled_date').removeAttribute('name');
            document.getElementById('scheduled_time').removeAttribute('name');
        }
    });
</script>
@endsection
