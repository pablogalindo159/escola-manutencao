<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Minha Área') - {{ config('app.name', 'Escola da Manutenção') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gray-50">
    <nav class="bg-white border-b sticky top-0 z-30">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-3">
                <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2 shrink-0">
                    <span class="text-2xl">🔧</span>
                    <span class="text-lg font-bold text-blue-600 hidden sm:inline">Escola da Manutenção</span>
                </a>

                {{-- Desktop: todos os links numa linha só, cabe tranquilo --}}
                <div class="hidden md:flex items-center gap-6 text-sm">
                    <a href="{{ route('student.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium">Meus Cursos</a>
                    <a href="{{ route('student.courses.index') }}" class="text-gray-700 hover:text-blue-600 font-medium">Todos os Cursos</a>
                    <a href="{{ route('student.community.index') }}" class="text-gray-700 hover:text-blue-600 font-medium">Comunidade</a>
                    <a href="{{ route('student.profile.edit') }}" class="text-gray-700 hover:text-blue-600 font-medium">Perfil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-700 font-medium">Sair</button>
                    </form>
                </div>

                {{-- Mobile: menu principal fica no menu inferior fixo; aqui só o que sobra (Perfil/Sair) --}}
                <details class="md:hidden relative">
                    <summary class="list-none cursor-pointer p-2 -mr-2 text-gray-700 [&::-webkit-details-marker]:hidden">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                    </summary>
                    <div class="absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded-lg shadow-lg py-1.5 z-30">
                        <a href="{{ route('student.profile.edit') }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">Perfil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-gray-50">Sair</button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24 md:pb-8">
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-800 text-sm">
                {{ session('warning') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>

    {{-- Mobile: menu inferior fixo com os destinos principais --}}
    <nav class="md:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 z-30">
        <div class="grid grid-cols-4">
            <a href="{{ route('student.dashboard') }}" class="flex flex-col items-center justify-center gap-0.5 py-2.5 {{ request()->routeIs('student.dashboard') ? 'text-blue-600' : 'text-gray-500' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V10"/></svg>
                <span class="text-[10px] font-medium">Meus Cursos</span>
            </a>
            <a href="{{ route('student.courses.index') }}" class="flex flex-col items-center justify-center gap-0.5 py-2.5 {{ request()->routeIs('student.courses.index') ? 'text-blue-600' : 'text-gray-500' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                <span class="text-[10px] font-medium">Todos</span>
            </a>
            <a href="{{ route('student.community.index') }}" class="flex flex-col items-center justify-center gap-0.5 py-2.5 {{ request()->routeIs('student.community.*') ? 'text-blue-600' : 'text-gray-500' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                <span class="text-[10px] font-medium">Comunidade</span>
            </a>
            <a href="{{ route('student.profile.edit') }}" class="flex flex-col items-center justify-center gap-0.5 py-2.5 {{ request()->routeIs('student.profile.*') ? 'text-blue-600' : 'text-gray-500' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                <span class="text-[10px] font-medium">Perfil</span>
            </a>
        </div>
    </nav>
</body>
</html>
