@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard Administrativo</h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Users -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Alunos Ativos</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($metrics['total_users']) }}</p>
                    </div>
                    <div class="text-4xl text-blue-600 opacity-20">👥</div>
                </div>
                <p class="text-green-600 text-sm mt-4">↑ {{ $metrics['active_students'] }} esta semana</p>
            </div>

            <!-- Revenue -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Receita Total</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">R$ {{ number_format($metrics['total_revenue'], 2, ',', '.') }}</p>
                    </div>
                    <div class="text-4xl text-green-600 opacity-20">💰</div>
                </div>
                <p class="text-green-600 text-sm mt-4">↑ 12% este mês</p>
            </div>

            <!-- Active Subscriptions -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Inscrições Ativas</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($metrics['active_subscriptions']) }}</p>
                    </div>
                    <div class="text-4xl text-purple-600 opacity-20">📚</div>
                </div>
                <p class="text-green-600 text-sm mt-4">↑ 8% este mês</p>
            </div>

            <!-- Completion Rate -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Taxa Conclusão</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $metrics['completion_rate'] }}%</p>
                    </div>
                    <div class="text-4xl text-orange-600 opacity-20">📈</div>
                </div>
                <p class="text-green-600 text-sm mt-4">Meta: 80%</p>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Inscriptions Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Inscrições (Últimos 7 dias)</h3>
                <div class="h-64 flex items-end gap-2">
                    @foreach($inscriptions_7d as $date => $count)
                        <div class="flex flex-col items-center gap-2 flex-1">
                            <div class="bg-blue-600 rounded-t-lg w-full" style="height: {{ max(20, $count * 20) }}px;"></div>
                            <span class="text-xs text-gray-600">{{ Carbon\Carbon::parse($date)->format('d/m') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Revenue by Method -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Receita por Método</h3>
                <div class="space-y-4">
                    @foreach($revenue_by_method as $method)
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">
                                    @switch($method->payment_method)
                                        @case('pix')
                                            PIX
                                        @break
                                        @case('credit_card')
                                            Cartão
                                        @break
                                        @case('boleto')
                                            Boleto
                                        @break
                                        @default
                                            {{ $method->payment_method }}
                                    @endswitch
                                </span>
                                <span class="text-sm font-semibold text-gray-900">
                                    R$ {{ number_format($method->total, 2, ',', '.') }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div 
                                    class="bg-blue-600 h-2 rounded-full" 
                                    style="width: {{ $revenue_by_method->sum('total') > 0 ? ($method->total / $revenue_by_method->sum('total') * 100) : 0 }}%"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Courses and Payments -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Popular Courses -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Cursos Populares</h3>
                    <a href="#" class="text-blue-600 text-sm hover:text-blue-700">Ver Tudo →</a>
                </div>
                <div class="space-y-4">
                    @foreach($popular_courses as $course)
                        <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900">{{ $course->title }}</p>
                                <p class="text-sm text-gray-600">{{ $course->students_count }} alunos inscritos</p>
                            </div>
                            <a href="#" class="text-blue-600 hover:text-blue-700">
                                Editar →
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Últimas Transações</h3>
                    <a href="#" class="text-blue-600 text-sm hover:text-blue-700">Ver Tudo →</a>
                </div>
                <div class="space-y-2">
                    @foreach($recent_payments as $payment)
                        <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg text-sm">
                            <div>
                                <p class="font-medium text-gray-900">{{ $payment->user->name }}</p>
                                <p class="text-gray-600">{{ $payment->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">R$ {{ number_format($payment->amount, 2, ',', '.') }}</p>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                                    Completo
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.sidebar')

@push('scripts')
<!-- Charts.js para gráficos mais avançados -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Implementar gráficos interativos aqui
</script>
@endpush
@endsection
