@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-8">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Pagar com PIX</h1>
            <p class="text-gray-600">{{ $course->title }}</p>
            <p class="text-3xl font-bold text-indigo-600 mt-4">R$ {{ number_format($course->price, 2, ',', '.') }}</p>
        </div>

        <!-- QR Code Section -->
        <div id="qr-code-container" class="bg-gray-50 rounded-lg p-6 mb-6 text-center hidden">
            <p class="text-sm text-gray-600 mb-3">Escaneie o código QR com seu celular</p>
            <div id="qr-code" class="flex justify-center mb-4"></div>
            
            <!-- Copy & Paste Code -->
            <div class="bg-white border border-gray-300 rounded p-4 mb-4">
                <p class="text-xs text-gray-600 mb-2">Ou copie o código PIX:</p>
                <div class="flex items-center gap-2">
                    <input 
                        type="text" 
                        id="pix-code" 
                        readonly 
                        class="flex-1 bg-gray-100 p-2 rounded text-xs font-mono border border-gray-300"
                        value=""
                    >
                    <button 
                        id="copy-btn" 
                        class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition text-sm"
                        onclick="copiarPix()"
                    >
                        Copiar
                    </button>
                </div>
            </div>

            <!-- Timer -->
            <div class="text-sm text-gray-600 mb-4">
                Vencimento em: <span id="timer" class="font-bold text-red-600">10:00</span>
            </div>

            <!-- Instructions -->
            <div class="bg-blue-50 border border-blue-200 rounded p-4 text-left mb-4">
                <p class="text-xs font-bold text-blue-900 mb-2">Como pagar:</p>
                <ol class="text-xs text-blue-800 space-y-1">
                    <li>1. Abra seu banco ou app de pagamento</li>
                    <li>2. Escolha PIX</li>
                    <li>3. Escaneie o QR ou cola o código</li>
                    <li>4. Confirme o pagamento</li>
                </ol>
            </div>

            <!-- Status -->
            <div id="status-pending" class="text-sm text-gray-600">
                Aguardando pagamento...
            </div>
            <div id="status-success" class="text-sm text-green-600 font-bold hidden">
                ✓ Pagamento aprovado!
            </div>
        </div>

        <!-- Loading -->
        <div id="loading" class="text-center">
            <div class="inline-block animate-spin">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </div>
            <p class="text-gray-600 mt-3">Gerando QR Code...</p>
        </div>

        <!-- Error -->
        <div id="error-container" class="bg-red-50 border border-red-200 rounded p-4 text-center hidden">
            <p class="text-red-700 text-sm" id="error-message"></p>
            <button 
                onclick="location.reload()" 
                class="mt-4 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition text-sm w-full"
            >
                Tentar Novamente
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

<!-- QR Code Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
let paymentId = null;
let timerInterval = null;
let pollingInterval = null;
let startTime = Date.now();
const TIMEOUT_MINUTES = 10;
const TIMEOUT_MS = TIMEOUT_MINUTES * 60 * 1000;

document.addEventListener('DOMContentLoaded', async function() {
    await gerarPix();
    iniciarPolling();
    iniciarTimer();
});

async function gerarPix() {
    try {
        const course = {{ $course->id }};
        
        // Obter CSRF token com fallback
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.content : '{{ csrf_token() }}';
        
        const response = await fetch(`/minha-area/cursos/${course}/gerar-pix`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Erro ao gerar PIX');
        }

        const data = await response.json();
        paymentId = data.payment_id;

        // Exibir QR Code
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('qr-code-container').classList.remove('hidden');

        // Gerar QR Code visual
        new QRCode(document.getElementById('qr-code'), {
            text: data.qr_code,
            width: 200,
            height: 200,
            colorDark: '#000',
            colorLight: '#fff',
        });

        // Exibir código PIX
        document.getElementById('pix-code').value = data.copy_paste;

    } catch (error) {
        console.error('Erro:', error);
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('error-container').classList.remove('hidden');
        document.getElementById('error-message').textContent = error.message;
    }
}

function copiarPix() {
    const codigo = document.getElementById('pix-code').value;
    navigator.clipboard.writeText(codigo).then(() => {
        const btn = document.getElementById('copy-btn');
        const texto = btn.textContent;
        btn.textContent = 'Copiado!';
        setTimeout(() => {
            btn.textContent = texto;
        }, 2000);
    });
}

function iniciarTimer() {
    const timerEl = document.getElementById('timer');
    timerInterval = setInterval(() => {
        const elapsed = Date.now() - startTime;
        const remaining = Math.max(0, TIMEOUT_MS - elapsed);
        const minutes = Math.floor(remaining / 60000);
        const seconds = Math.floor((remaining % 60000) / 1000);
        timerEl.textContent = `${minutes}:${String(seconds).padStart(2, '0')}`;

        if (remaining <= 0) {
            clearInterval(timerInterval);
            document.getElementById('qr-code-container').classList.add('hidden');
            document.getElementById('error-container').classList.remove('hidden');
            document.getElementById('error-message').textContent = 'QR Code expirou. Recarregue a página para gerar um novo.';
            clearInterval(pollingInterval);
        }
    }, 1000);
}

function iniciarPolling() {
    pollingInterval = setInterval(async () => {
        if (!paymentId) return;

        try {
            const course = {{ $course->id }};
            const response = await fetch(`/minha-area/cursos/${course}/status-pix/${paymentId}`);
            const data = await response.json();

            if (data.approved) {
                clearInterval(pollingInterval);
                clearInterval(timerInterval);

                document.getElementById('status-pending').classList.add('hidden');
                document.getElementById('status-success').classList.remove('hidden');

                setTimeout(() => {
                    window.location.href = '{{ route("student.dashboard") }}';
                }, 2000);
            }
        } catch (error) {
            console.error('Erro ao verificar status:', error);
        }
    }, 3000);
}

window.addEventListener('beforeunload', () => {
    clearInterval(timerInterval);
    clearInterval(pollingInterval);
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
