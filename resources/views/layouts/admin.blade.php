<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - {{ config('app.name', 'Escola da Manutenção') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @include('partials.pwa-head')
</head>
<body class="antialiased bg-gray-100">
    @yield('content')
    @stack('scripts')
</body>
</html>
