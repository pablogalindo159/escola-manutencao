<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n===== MERCADO PAGO DEBUG =====\n\n";
echo "[1] Verificando credenciais no banco...\n";

$accessToken = \App\Models\Setting::get('mercado_pago_access_token');
$publicKey = \App\Models\Setting::get('mercado_pago_public_key');
$environment = \App\Models\Setting::get('mercado_pago_environment');

if ($accessToken) {
    echo "Access Token: " . substr($accessToken, 0, 20) . "...\n";
} else {
    echo "Access Token: NOT FOUND\n";
}

if ($publicKey) {
    echo "Public Key: " . substr($publicKey, 0, 20) . "...\n";
} else {
    echo "Public Key: NOT FOUND\n";
}

echo "Environment: " . ($environment ?: "NOT FOUND") . "\n\n";

if (!$accessToken) {
    echo "ERROR: Access token nao configurado!\n";
    echo "Acesse /admin/configuracoes/mercado-pago e configure.\n";
} else {
    echo "[2] Buscando dados de teste...\n";
    $course = \App\Models\Course::where('type', 'paid')->where('status', 'published')->first();
    $user = \App\Models\User::where('role', 'student')->first();
    
    if ($course && $user) {
        echo "Curso: " . $course->title . " (ID " . $course->id . ")\n";
        echo "Preco: R$ " . $course->price . "\n";
        echo "Usuario: " . $user->name . "\n\n";
        
        echo "[3] Testando criacao de preference...\n";
        try {
            $service = new \App\Services\MercadoPagoService();
            $preference = $service->createPreference($course, $user);
            
            echo "Preference ID: " . $preference['id'] . "\n";
            
            if (isset($preference['init_point']) && $preference['init_point']) {
                echo "Init Point: " . $preference['init_point'] . "\n\n";
                echo "SUCCESS! Checkout link gerado com sucesso!\n";
            } else {
                echo "ERROR: init_point vazio!\n";
                echo "Response:\n";
                print_r($preference);
            }
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    } else {
        if (!$course) echo "ERROR: Nenhum curso paid/published encontrado\n";
        if (!$user) echo "ERROR: Nenhum usuario student encontrado\n";
    }
}

echo "\n=============================\n\n";
