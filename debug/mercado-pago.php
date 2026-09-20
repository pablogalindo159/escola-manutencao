<?php
/**
 * DEBUG MERCADO PAGO - Tinker Script
 * 
 * Uso:
 * $ php artisan tinker
 * >>> include 'debug/mercado-pago.php'
 */

echo "\n========== MERCADO PAGO DEBUG ==========\n\n";

// 1. Verificar credenciais no banco
echo "1️⃣  CREDENCIAIS NO BANCO:\n";
$accessToken = \App\Models\Setting::get('mercado_pago_access_token');
$publicKey = \App\Models\Setting::get('mercado_pago_public_key');
$webhookSecret = \App\Models\Setting::get('mercado_pago_webhook_secret');
$environment = \App\Models\Setting::get('mercado_pago_environment');

echo "   Access Token: " . (substr($accessToken ?? '', 0, 30) . '...' ) . "\n";
echo "   Public Key: " . (substr($publicKey ?? '', 0, 30) . '...' ) . "\n";
echo "   Webhook Secret: " . (substr($webhookSecret ?? '', 0, 30) . '...' ) . "\n";
echo "   Environment: " . ($environment ?? '❌ NOT FOUND') . "\n\n";

if (!$accessToken) {
    echo "❌ ERRO: Access token não configurado no banco!\n";
    echo "   Acesse: /admin/configuracoes/mercado-pago e configure.\n";
    return;
}

// 2. Buscar dados de teste
echo "2️⃣  BUSCANDO DADOS DE TESTE:\n";
$course = \App\Models\Course::where('type', 'paid')->where('status', 'published')->first();
$user = \App\Models\User::where('role', 'student')->first();

if (!$course) {
    echo "   ❌ Nenhum curso paid/published encontrado\n";
    return;
}
if (!$user) {
    echo "   ❌ Nenhum usuário student encontrado\n";
    return;
}

echo "   ✓ Curso: {$course->title} (ID {$course->id}) - R$ {$course->price}\n";
echo "   ✓ Usuário: {$user->name} ({$user->email}) - ID {$user->id}\n\n";

// 3. Testar criação de preference
echo "3️⃣  TESTANDO CRIAÇÃO DE PREFERENCE:\n";
try {
    $service = new \App\Services\MercadoPagoService();
    $preference = $service->createPreference($course, $user);
    
    echo "   ✓ Resposta recebida!\n";
    echo "   📍 Preference ID: {$preference['id']}\n";
    
    if (!isset($preference['init_point']) || !$preference['init_point']) {
        echo "   ❌ ERRO: init_point vazio!\n";
        echo "   Response completa:\n";
        dd($preference);
    }
    
    echo "   🔗 Init Point (checkout URL):\n";
    echo "      {$preference['init_point']}\n\n";
    echo "   ✅ Status: FUNCIONANDO!\n";
    
} catch (\Exception $e) {
    echo "   ❌ ERRO ao criar preference:\n";
    echo "   {$e->getMessage()}\n\n";
}

echo "==========================================\n\n";
