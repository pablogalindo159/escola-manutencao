@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="bg-white shadow-sm">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900">Editar Curso</h1>
            <a href="{{ route('admin.courses') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">← Voltar aos cursos</a>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800 text-sm">
                {{ session('warning') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.courses.update', $course) }}" class="bg-white rounded-lg shadow p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                <input type="text" name="title" value="{{ old('title', $course->title) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea name="description" rows="4" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('description', $course->description) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <input type="text" name="category" value="{{ old('category', $course->category) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nível</label>
                    <select name="level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @foreach (['beginner' => 'Iniciante', 'intermediate' => 'Intermediário', 'advanced' => 'Avançado'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('level', $course->level) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duração (min)</label>
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $course->duration_minutes) }}" required min="1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preço (R$)</label>
                    <input type="number" step="0.01" name="price" value="{{ old('price', $course->price) }}" required min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @foreach (['draft' => 'Rascunho', 'published' => 'Publicado', 'archived' => 'Arquivado'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $course->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <a href="{{ route('admin.courses') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">
                    Salvar alterações
                </button>
            </div>
        </form>

        <!-- ==================== MATRICULAR ALUNO ==================== -->
        <div class="mt-10 bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-1">👤 Matricular Aluno Manualmente</h2>
            <p class="text-sm text-gray-500 mb-4">
                Use isso pra matricular quem pagou por fora (PIX, dinheiro) ou dar acesso de cortesia -
                enquanto o pagamento online não está pronto.
            </p>
            <form method="POST" action="{{ route('admin.courses.enroll', $course) }}" class="flex gap-3">
                @csrf
                <input type="email" name="email" required placeholder="email@doaluno.com"
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 whitespace-nowrap">
                    Matricular
                </button>
            </form>
            <p class="text-xs text-gray-400 mt-2">O aluno precisa já ter uma conta criada (cadastro em /cadastro).</p>
        </div>

        <!-- ==================== VÍDEOS DO CURSO ==================== -->
        <div class="mt-10">
            <h2 class="text-xl font-bold text-gray-900 mb-4">🎬 Vídeos do Curso</h2>

            <div class="bg-white rounded-lg shadow overflow-hidden mb-4">
                @if($videos->count())
                    <div class="divide-y divide-gray-200">
                        @foreach ($videos as $video)
                            <details class="group">
                                <summary class="flex items-center justify-between gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 list-none">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="text-gray-400 text-sm w-6 shrink-0">{{ $video->order }}</span>
                                        <span class="font-medium text-gray-900 truncate">{{ $video->title }}</span>
                                        @php
                                            $statusColors = [
                                                'published' => 'bg-green-100 text-green-700',
                                                'draft' => 'bg-yellow-100 text-yellow-700',
                                                'archived' => 'bg-gray-100 text-gray-700',
                                            ];
                                        @endphp
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium shrink-0 {{ $statusColors[$video->status] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ ucfirst($video->status) }}
                                        </span>
                                    </div>
                                    <span class="text-blue-600 text-sm font-medium shrink-0 group-open:hidden">Editar</span>
                                    <span class="text-gray-500 text-sm font-medium shrink-0 hidden group-open:inline">Fechar</span>
                                </summary>

                                <div class="px-4 pb-4 pt-1 bg-gray-50 border-t">
                                    <form method="POST" action="{{ route('admin.videos.update', [$course, $video]) }}" class="space-y-3 pt-3">
                                        @csrf
                                        @method('PUT')

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Título</label>
                                            <input type="text" name="title" value="{{ old('title', $video->title) }}" required
                                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Descrição</label>
                                            <textarea name="description" rows="2"
                                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('description', $video->description) }}</textarea>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">URL do Vídeo</label>
                                            <input type="url" name="video_url" value="{{ old('video_url', $video->video_url) }}" required
                                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <div class="grid grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Duração (seg)</label>
                                                <input type="number" name="duration_seconds" value="{{ old('duration_seconds', $video->duration_seconds) }}" required min="0"
                                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Ordem</label>
                                                <input type="number" name="order" value="{{ old('order', $video->order) }}" required min="0"
                                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Qualidade</label>
                                                <select name="quality" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                    @foreach (['480p', '720p', '1080p'] as $q)
                                                        <option value="{{ $q }}" @selected(old('quality', $video->quality) === $q)>{{ $q }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Thumbnail (opcional)</label>
                                                <input type="url" name="thumbnail_url" value="{{ old('thumbnail_url', $video->thumbnail_url) }}"
                                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Material de apoio (opcional)</label>
                                                <input type="url" name="material_url" value="{{ old('material_url', $video->material_url) }}"
                                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                                            <select name="status" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="draft" @selected($video->status === 'draft')>Rascunho (não aparece pros alunos)</option>
                                                <option value="published" @selected($video->status === 'published')>Publicado</option>
                                                <option value="archived" @selected($video->status === 'archived')>Arquivado</option>
                                            </select>
                                        </div>

                                        <div class="flex justify-between items-center pt-2">
                                            <button type="submit" form="delete-video-{{ $video->id }}"
                                                    onclick="return confirm('Remover este vídeo?')"
                                                    class="text-red-600 hover:text-red-700 text-sm font-medium">
                                                🗑️ Remover vídeo
                                            </button>
                                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700">
                                                Salvar vídeo
                                            </button>
                                        </div>
                                    </form>
                                    <form id="delete-video-{{ $video->id }}" action="{{ route('admin.videos.destroy', [$course, $video]) }}" method="POST" class="hidden">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </details>
                        @endforeach
                    </div>
                @else
                    <p class="px-4 py-6 text-center text-gray-500 text-sm">Nenhum vídeo adicionado ainda.</p>
                @endif
            </div>

            <!-- Adicionar novo vídeo, na mesma página -->
            <details class="bg-white rounded-lg shadow">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-semibold text-blue-600 list-none flex items-center gap-2">
                    ➕ Adicionar Vídeo
                </summary>
                <div class="px-4 pb-4 pt-1 border-t">
                    <form method="POST" action="{{ route('admin.videos.store', $course) }}" class="space-y-3 pt-3">
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Título</label>
                            <input type="text" name="title" id="new-video-title" value="{{ old('title') }}" required
                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Descrição</label>
                            <textarea name="description" rows="2"
                                class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('description') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">URL do Vídeo</label>
                            <div class="flex gap-2">
                                <input type="url" name="video_url" id="new-video-url" value="{{ old('video_url') }}" required
                                    placeholder="https://..."
                                    class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <button type="button" onclick="detectVideoData()"
                                    class="px-3 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 whitespace-nowrap">
                                    🔍 Detectar dados
                                </button>
                            </div>
                            <p id="detect-status" class="text-xs mt-1"></p>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Duração (seg)</label>
                                <input type="number" name="duration_seconds" id="new-video-duration" value="{{ old('duration_seconds', 0) }}" required min="0"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Ordem</label>
                                <input type="number" name="order" value="{{ old('order', $videos->max('order') + 1) }}" required min="0"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Qualidade</label>
                                <select name="quality" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    @foreach (['480p', '720p', '1080p'] as $q)
                                        <option value="{{ $q }}" @selected(old('quality', '720p') === $q)>{{ $q }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Thumbnail (opcional)</label>
                                <input type="url" name="thumbnail_url" id="new-video-thumbnail" value="{{ old('thumbnail_url') }}"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Material de apoio (opcional)</label>
                                <input type="url" name="material_url" value="{{ old('material_url') }}"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <p class="text-xs text-gray-500">O vídeo será salvo como rascunho - edite depois pra publicar.</p>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700">
                                Adicionar vídeo
                            </button>
                        </div>
                    </form>
                </div>
            </details>
        </div>
    </div>
</div>

<script>
async function detectVideoData() {
    const url = document.getElementById('new-video-url').value;
    const status = document.getElementById('detect-status');

    if (!url) {
        status.textContent = 'Cole a URL do vídeo primeiro.';
        status.className = 'text-xs mt-1 text-red-600';
        return;
    }

    status.textContent = 'Buscando dados...';
    status.className = 'text-xs mt-1 text-gray-500';

    try {
        const response = await fetch('{{ route("admin.videos.detect", $course) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ video_url: url }),
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            status.textContent = data.message || 'Não foi possível detectar os dados.';
            status.className = 'text-xs mt-1 text-red-600';
            return;
        }

        if (data.title) {
            document.getElementById('new-video-title').value = data.title;
        }
        if (data.duration_seconds) {
            document.getElementById('new-video-duration').value = data.duration_seconds;
        }
        if (data.thumbnail_url) {
            document.getElementById('new-video-thumbnail').value = data.thumbnail_url;
        }

        status.textContent = data.note || '✅ Dados detectados!';
        status.className = 'text-xs mt-1 ' + (data.note ? 'text-yellow-600' : 'text-green-600');
    } catch (e) {
        status.textContent = 'Erro ao detectar dados. Preencha manualmente.';
        status.className = 'text-xs mt-1 text-red-600';
    }
}
</script>
@endsection
