@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="bg-white shadow-sm">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Vídeos do Curso</h1>
                <p class="text-gray-600 mt-1">{{ $course->title }}</p>
            </div>
            <a href="{{ route('admin.courses') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">← Voltar aos cursos</a>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex justify-end mb-6">
            <a href="{{ route('admin.videos.create', $course) }}"
               class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-semibold">
                + Adicionar Vídeo
            </a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            @if($videos->count())
                <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ordem</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duração</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qualidade</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($videos as $video)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $video->order }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $video->title }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ sprintf('%d:%02d', intdiv($video->duration_seconds, 60), $video->duration_seconds % 60) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $video->quality }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusColors = [
                                            'published' => 'bg-green-100 text-green-700',
                                            'draft' => 'bg-yellow-100 text-yellow-700',
                                            'archived' => 'bg-gray-100 text-gray-700',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$video->status] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ ucfirst($video->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.videos.edit', [$course, $video]) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Editar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <p class="text-gray-600 text-lg mb-4">Nenhum vídeo cadastrado ainda.</p>
                    <a href="{{ route('admin.videos.create', $course) }}"
                       class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        Adicionar o primeiro vídeo
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
