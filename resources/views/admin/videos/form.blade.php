@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <a href="{{ route('admin.videos.index', $course) }}" class="text-blue-600 hover:underline mb-4 inline-block">← Voltar</a>
            <h1 class="text-3xl font-bold text-gray-900">
                {{ isset($video) ? 'Editar Vídeo' : 'Adicionar Vídeo' }}
            </h1>
            <p class="text-gray-600 mt-1">{{ $course->title }}</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="bg-white rounded-lg shadow p-8">
            <form action="{{ isset($video) ? route('admin.videos.update', [$course, $video]) : route('admin.videos.store', $course) }}"
                  method="POST" class="space-y-5">
                @csrf
                @if(isset($video))
                    @method('PUT')
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                    <input type="text" name="title" value="{{ old('title', $video->title ?? '') }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('description', $video->description ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL do Vídeo</label>
                    <input type="url" name="video_url" value="{{ old('video_url', $video->video_url ?? '') }}" required
                        placeholder="https://..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Link direto do vídeo hospedado (S3, Vimeo, CDN, etc). O vídeo não é enviado pra este servidor.</p>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duração (segundos)</label>
                        <input type="number" name="duration_seconds" value="{{ old('duration_seconds', $video->duration_seconds ?? 0) }}" required min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ordem</label>
                        <input type="number" name="order" value="{{ old('order', $video->order ?? $nextOrder ?? 0) }}" required min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Qualidade</label>
                        <select name="quality" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            @foreach (['480p', '720p', '1080p'] as $q)
                                <option value="{{ $q }}" @selected(old('quality', $video->quality ?? '720p') === $q)>{{ $q }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thumbnail (opcional)</label>
                    <input type="url" name="thumbnail_url" value="{{ old('thumbnail_url', $video->thumbnail_url ?? '') }}"
                        placeholder="https://..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Material de apoio (opcional)</label>
                    <input type="url" name="material_url" value="{{ old('material_url', $video->material_url ?? '') }}"
                        placeholder="https://... (PDF, apostila, etc)"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                @if(isset($video))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="draft" @selected($video->status === 'draft')>Rascunho (não aparece pros alunos)</option>
                            <option value="published" @selected($video->status === 'published')>Publicado</option>
                            <option value="archived" @selected($video->status === 'archived')>Arquivado</option>
                        </select>
                    </div>
                @else
                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-700 text-sm">
                        O vídeo será salvo como <strong>rascunho</strong>. Edite-o depois pra publicar.
                    </div>
                @endif

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('admin.videos.index', $course) }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancelar</a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">
                        {{ isset($video) ? 'Salvar alterações' : 'Adicionar vídeo' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
