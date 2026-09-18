@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="bg-white shadow-sm">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900">Novo Curso</h1>
            <a href="{{ route('admin.courses') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">← Voltar aos cursos</a>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.courses.store') }}" class="bg-white rounded-lg shadow p-6 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                    placeholder="Ex: Manutenção de Impressoras"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea name="description" rows="4" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <input type="text" name="category" value="{{ old('category') }}" required
                        placeholder="Ex: Impressoras"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nível</label>
                    <select name="level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @foreach (['beginner' => 'Iniciante', 'intermediate' => 'Intermediário', 'advanced' => 'Avançado'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('level') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duração (min)</label>
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', 60) }}" required min="1"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select name="type" id="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="paid" @selected(old('type', 'paid') === 'paid')>Pago</option>
                        <option value="free" @selected(old('type') === 'free')>Gratuito</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preço (R$)</label>
                    <input type="number" step="0.01" name="price" value="{{ old('price', 0) }}" required min="0"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Imagem de capa (URL, opcional)</label>
                <input type="url" name="thumbnail_url" value="{{ old('thumbnail_url') }}"
                    placeholder="https://..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="featured" name="featured" value="1" @checked(old('featured'))
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                <label for="featured" class="ml-3 text-gray-900">
                    Destacar na página inicial <span class="text-gray-500 text-sm">(precisa estar publicado pra aparecer)</span>
                </label>
            </div>

            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-700 text-sm">
                O curso será criado como <strong>rascunho</strong>. Depois de adicionar os vídeos, edite o curso e mude o status pra "Publicado".
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <a href="{{ route('admin.courses') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">
                    Criar curso
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
