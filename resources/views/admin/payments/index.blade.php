@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-3xl font-bold text-gray-900">Pagamentos</h1>
            <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">← Voltar ao Dashboard</a>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aluno</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $payment->user->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">R$ {{ number_format($payment->amount, 2, ',', '.') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $payment->method ? ucfirst(str_replace('_', ' ', $payment->method)) : '—' }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'approved' => 'bg-green-100 text-green-700',
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                        'cancelled' => 'bg-gray-100 text-gray-700',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$payment->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">Nenhum pagamento registrado ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection
