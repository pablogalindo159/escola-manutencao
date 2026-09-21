<?php
// Script direto para corrigir admin - sem tinker

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "════════════════════════════════════════════════════════════\n";
echo "  CORRIGINDO ADMIN - MÉTODO DIRETO\n";
echo "════════════════════════════════════════════════════════════\n\n";

// 1. Checar estado atual
echo "1️⃣  Estado atual no banco:\n";
$current = DB::table('users')->where('email', 'admin@example.com')->first();
if ($current) {
    echo "   Email: {$current->email}\n";
    echo "   Role: {$current->role}\n";
    echo "   Status: {$current->status}\n\n";
} else {
    echo "   Usuário não encontrado!\n\n";
}

// 2. Atualizar direto no banco com DB::table (mais confiável que ORM)
echo "2️⃣  Atualizando role='admin' direto no banco...\n";
$updated = DB::table('users')
    ->where('email', 'admin@example.com')
    ->update([
        'role' => 'admin',
        'status' => 'active',
        'updated_at' => now(),
    ]);
echo "   ✅ Atualizado: $updated registro(s)\n\n";

// 3. Verificar resultado
echo "3️⃣  Verificação pós-atualização:\n";
$result = DB::table('users')->where('email', 'admin@example.com')->first();
if ($result) {
    echo "   Email: {$result->email}\n";
    echo "   Role: {$result->role}\n";
    echo "   Status: {$result->status}\n";
    
    if ($result->role === 'admin') {
        echo "   ✅ SUCESSO! Role agora é 'admin'\n\n";
    } else {
        echo "   ❌ ERRO! Role ainda não é 'admin'\n\n";
    }
} else {
    echo "   ❌ Usuário desapareceu!\n\n";
}

// 4. Se não existir, criar do zero
if (!$result) {
    echo "4️⃣  Criando admin do zero...\n";
    User::create([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => Hash::make('Senha123!'),
        'role' => 'admin',
        'status' => 'active',
    ]);
    echo "   ✅ Admin criado!\n\n";
}

// 5. Listar todos os usuários para debug
echo "5️⃣  Todos os usuários no banco:\n";
$users = DB::table('users')->get();
foreach ($users as $u) {
    echo "   ID: {$u->id} | Email: {$u->email} | Role: {$u->role}\n";
}

echo "\n════════════════════════════════════════════════════════════\n";
echo "  ✅ Pronto! Execute: systemctl restart php8.3-fpm\n";
echo "  E teste: https://escola.informaticasaojose.srv.br/login\n";
echo "════════════════════════════════════════════════════════════\n";
