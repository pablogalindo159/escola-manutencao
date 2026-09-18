@extends('layouts.student')

@section('title', 'Novo Post')

@section('content')
<a href="{{ route('student.community.index') }}" class="text-blue-600 hover:underline text-sm mb-4 inline-block">← Comunidade</a>

<div class="bg-white rounded-xl shadow p-6">
    <h1 class="text-xl font-bold text-gray-900 mb-6">Novo Post</h1>

    <form method="POST" action="{{ route('student.community.store') }}" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Curso</label>
            <select name="course_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Selecione um curso</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" @selected(old('course_id', $selectedCourseId) == $course->id)>{{ $course->title }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
            <input type="text" name="title" value="{{ old('title') }}" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem</label>
            <textarea name="content" rows="6" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('content') }}</textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('student.community.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">
                Publicar
            </button>
        </div>
    </form>
</div>
@endsection
