<?php
// Script para corrigir role do admin - executar na raiz do projeto Laravel

// Carregar Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "════════════════════════════════════════════════════════════\n";
echo "  CORRIGINDO ADMIN - ADICIONANDO ROLE='ADMIN'\n";
echo "════════════════════════════════════════════════════════════\n\n";

// 1. Deletar admin antigo (com role=student)
echo "1️⃣  Deletando admin@example.com antigo...\n";
$deleted = User::where('email', 'admin@example.com')->delete();
echo "   ✅ Deletado: $deleted usuário(s)\n\n";

// 2. Criar novo admin com role correto
echo "2️⃣  Criando novo admin com role='admin'...\n";
$admin = User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => Hash::make('Senha123!'),
    'role' => 'admin',
    'status' => 'active',
]);
echo "   ✅ Usuário criado:\n";
echo "      ID: {$admin->id}\n";
echo "      Nome: {$admin->name}\n";
echo "      Email: {$admin->email}\n";
echo "      Role: {$admin->role}\n";
echo "      Status: {$admin->status}\n\n";

// 3. Verificar
echo "3️⃣  Verificação final:\n";
$verify = User::where('email', 'admin@example.com')->first();
if ($verify && $verify->role === 'admin') {
    echo "   ✅ SUCESSO! Admin tem role='admin' correto!\n\n";
} else {
    echo "   ❌ ERRO! Role ainda não está correto.\n\n";
}

echo "════════════════════════════════════════════════════════════\n";
echo "  Agora execute: systemctl restart php8.3-fpm\n";
echo "  E acesse: https://escola.informaticasaojose.srv.br/login\n";
echo "════════════════════════════════════════════════════════════\n";
