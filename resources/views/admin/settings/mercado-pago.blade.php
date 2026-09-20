@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-6 px-4">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">⚙️ Configurações Mercado Pago</h1>
        <p class="text-gray-600 mt-2">Gerencie suas credenciais de pagamento PIX</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">
            <p class="font-bold">❌ Erro ao salvar:</p>
            <ul class="mt-2 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-lg p-6">
        <form action="{{ route('admin.settings.update-mercado-pago') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Informação -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                <p class="text-blue-800 text-sm">
                    <strong>💡 Dica:</strong> Encontre suas credenciais no 
                    <a href="https://www.mercadopago.com.br/developers/panel" target="_blank" class="underline font-bold hover:text-blue-900">
                        Painel de Desenvolvedores do Mercado Pago
                    </a>
                </p>
            </div>

            <!-- Access Token -->
            <div>
                <label for="access_token" class="block text-sm font-medium text-gray-700 mb-2">
                    🔑 Access Token
                </label>
                <input 
                    type="password" 
                    id="access_token"
                    name="access_token" 
                    value="{{ $mpSettings['access_token'] ?? '' }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('access_token') border-red-500 @enderror"
                    placeholder="APP_USR_..."
                    required
                >
                <p class="text-xs text-gray-500 mt-1">
                    Começará com <code class="bg-gray-100 px-2 py-1 rounded">APP_USR_</code>
                </p>
                @error('access_token')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Public Key -->
            <div>
                <label for="public_key" class="block text-sm font-medium text-gray-700 mb-2">
                    🔓 Public Key
                </label>
                <input 
                    type="password" 
                    id="public_key"
                    name="public_key" 
                    value="{{ $mpSettings['public_key'] ?? '' }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('public_key') border-red-500 @enderror"
                    placeholder="APP_USR_..."
                    required
                >
                <p class="text-xs text-gray-500 mt-1">
                    Começará com <code class="bg-gray-100 px-2 py-1 rounded">APP_USR_</code>
                </p>
                @error('public_key')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Webhook Secret -->
            <div>
                <label for="webhook_secret" class="block text-sm font-medium text-gray-700 mb-2">
                    🔐 Webhook Secret
                </label>
                <input 
                    type="password" 
                    id="webhook_secret"
                    name="webhook_secret" 
                    value="{{ $mpSettings['webhook_secret'] ?? '' }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('webhook_secret') border-red-500 @enderror"
                    placeholder="seu_webhook_secret"
                    required
                >
                <p class="text-xs text-gray-500 mt-1">
                    Configure em: Painel MP → Webhooks → Criar webhook
                </p>
                @error('webhook_secret')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Environment -->
            <div>
                <label for="environment" class="block text-sm font-medium text-gray-700 mb-2">
                    🌍 Ambiente
                </label>
                <select 
                    id="environment"
                    name="environment" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="sandbox" @selected(($mpSettings['environment'] ?? 'sandbox') == 'sandbox')>
                        🧪 Sandbox (Testes) — Use para testar antes de ir ao vivo
                    </option>
                    <option value="production" @selected(($mpSettings['environment'] ?? 'sandbox') == 'production')>
                        🚀 Production (Ao Vivo) — Cobranças reais
                    </option>
                </select>
            </div>

            <!-- Status Atual -->
            <div class="bg-gray-50 border-l-4 border-gray-400 p-4 rounded">
                <h3 class="font-bold text-gray-900 mb-3">📊 Status Atual</h3>
                <ul class="space-y-2 text-sm text-gray-700">
                    <li>
                        <strong>Ambiente:</strong> 
                        @if(($mpSettings['environment'] ?? 'sandbox') == 'production')
                            <span class="text-red-600 font-bold">🚀 Production</span>
                        @else
                            <span class="text-yellow-600 font-bold">🧪 Sandbox</span>
                        @endif
                    </li>
                    <li>
                        <strong>Access Token:</strong> 
                        {{ $mpSettings['access_token'] ? '✅ Configurado' : '❌ Não configurado' }}
                    </li>
                    <li>
                        <strong>Public Key:</strong> 
                        {{ $mpSettings['public_key'] ? '✅ Configurado' : '❌ Não configurado' }}
                    </li>
                    <li>
                        <strong>Webhook Secret:</strong> 
                        {{ $mpSettings['webhook_secret'] ? '✅ Configurado' : '❌ Não configurado' }}
                    </li>
                </ul>
            </div>

            <!-- Botões -->
            <div class="flex gap-4 pt-4">
                <button 
                    type="submit" 
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg transition duration-200 flex items-center gap-2"
                >
                    ✅ Salvar Configurações
                </button>
                <a 
                    href="{{ route('admin.dashboard') ?? '/' }}" 
                    class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded-lg transition duration-200"
                >
                    ← Voltar
                </a>
            </div>
        </form>
    </div>

    <!-- Links úteis -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="https://www.mercadopago.com.br/developers/panel" target="_blank" class="bg-blue-50 border border-blue-200 rounded-lg p-4 hover:bg-blue-100 transition">
            <p class="font-bold text-blue-900">🔐 Painel MP</p>
            <p class="text-sm text-blue-700">Acessar credenciais</p>
        </a>
        <a href="https://www.mercadopago.com.br/developers/pt/docs" target="_blank" class="bg-purple-50 border border-purple-200 rounded-lg p-4 hover:bg-purple-100 transition">
            <p class="font-bold text-purple-900">📚 Documentação</p>
            <p class="text-sm text-purple-700">Ver guia técnico</p>
        </a>
    </div>
</div>
@endsection
