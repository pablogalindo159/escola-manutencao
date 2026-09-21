@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-8">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Pagar com Mercado Pago</h1>
            <p class="text-gray-600">{{ $course->title }}</p>
            <p class="text-3xl font-bold text-indigo-600 mt-4">R$ {{ number_format($course->price, 2, ',', '.') }}</p>
        </div>

        <!-- Loading -->
        <div id="loading" class="text-center">
            <div class="inline-block animate-spin">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </div>
            <p class="text-gray-600 mt-3">Redirecionando para Mercado Pago...</p>
        </div>

        <!-- Error -->
        <div id="error-container" class="bg-red-50 border border-red-200 rounded p-4 text-center hidden">
            <p class="text-red-700 text-sm" id="error-message"></p>
            <button 
                onclick="window.location.href = '{{ route('student.courses.show', $course) }}'" 
                class="mt-4 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition text-sm w-full"
            >
                Voltar para o curso
            </button>
        </div>

        <!-- Back Button -->
        <a 
            href="{{ route('student.courses.show', $course) }}" 
            class="block text-center text-indigo-600 hover:text-indigo-700 text-sm mt-4 underline"
        >
            Voltar para o curso
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const course = {{ $course->id }};
        
        // Obter CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.content : '{{ csrf_token() }}';
        
        // Solicitar preference do Checkout Pro
        const response = await fetch(`/minha-area/cursos/${course}/criar-preference`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const responseText = await response.text();
        let data = {};
        
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            data = {
                error: `Servidor retornou HTTP ${response.status}`
            };
        }

        if (!response.ok) {
            throw new Error(
                data.error ||
                data.message ||
                `Erro HTTP ${response.status}`
            );
        }

        // Redirecionar para o link do Mercado Pago
        if (data.checkout_pro_url) {
            window.location.href = data.checkout_pro_url;
        } else {
            throw new Error('URL de checkout não disponível');
        }

    } catch (error) {
        console.error('Erro:', error);
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('error-container').classList.remove('hidden');
        document.getElementById('error-message').textContent = error.message;
    }
});
</script>

<style>
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
    
    .animate-spin {
        animation: spin 1s linear infinite;
    }
</style>
@endsection
