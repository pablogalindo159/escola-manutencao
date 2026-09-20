@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-3xl font-bold text-gray-900">Cursos</h1>
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.courses.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-semibold">+ Novo Curso</a>
                <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">← Voltar ao Dashboard</a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Curso</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Preço</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alunos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($courses as $course)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $course->title }}</div>
                                <div class="text-sm text-gray-500">{{ $course->instructor->name ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $course->category }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">R$ {{ number_format($course->price, 2, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $course->students_count }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'published' => 'bg-green-100 text-green-700',
                                        'draft' => 'bg-yellow-100 text-yellow-700',
                                        'archived' => 'bg-gray-100 text-gray-700',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$course->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($course->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.courses.edit', $course) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">Nenhum curso cadastrado ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $courses->links() }}
        </div>
    </div>
</div>
@endsection
