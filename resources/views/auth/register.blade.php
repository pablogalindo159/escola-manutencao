@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-blue-50 to-white flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
                <span class="text-3xl">🔧</span>
                <span class="text-2xl font-bold text-blue-600">Escola da Manutenção</span>
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Criar conta</h1>
            <p class="text-gray-600 text-sm mb-6">Cadastre-se para acessar os cursos pelo aplicativo.</p>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register.attempt') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Seu nome"
                    >
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email') }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="seu@email.com"
                    >
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            value="{{ old('phone') }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="(41) 99999-9999"
                        >
                    </div>
                    <div>
                        <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                        <input
                            type="text"
                            name="cpf"
                            id="cpf"
                            value="{{ old('cpf') }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="000.000.000-00"
                        >
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        minlength="8"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Mínimo 8 caracteres"
                    >
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar senha</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        required
                        minlength="8"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Repita a senha"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition"
                >
                    Criar conta
                </button>
            </form>
        </div>

        <p class="text-center text-sm text-gray-600 mt-6">
            Já tem conta? <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Fazer login</a>
        </p>
    </div>
</div>
@endsection
