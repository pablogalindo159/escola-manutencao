<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Minha Área') - {{ config('app.name', 'Escola da Manutenção') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased bg-gray-50">
    <nav class="bg-white border-b sticky top-0 z-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-3">
                <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2 shrink-0">
                    <span class="text-2xl">🔧</span>
                    <span class="text-lg font-bold text-blue-600 hidden sm:inline">Escola da Manutenção</span>
                </a>
                <div class="flex items-center gap-4 text-sm overflow-x-auto whitespace-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <a href="{{ route('student.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium shrink-0">Meus Cursos</a>
                    <a href="{{ route('student.courses.index') }}" class="text-gray-700 hover:text-blue-600 font-medium shrink-0">Todos os Cursos</a>
                    <a href="{{ route('student.community.index') }}" class="text-gray-700 hover:text-blue-600 font-medium shrink-0">Comunidade</a>
                    <a href="{{ route('student.profile.edit') }}" class="text-gray-700 hover:text-blue-600 font-medium shrink-0">Perfil</a>
                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-700 font-medium">Sair</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
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
</body>
</html>
