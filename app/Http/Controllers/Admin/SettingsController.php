<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\PaymentMethodsTrait;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{

    /**
     * Página de configurações Mercado Pago
     */
    public function mercadoPago()
    {
        $mpSettings = [
            'access_token' => Setting::get('mercado_pago_access_token'),
            'public_key' => Setting::get('mercado_pago_public_key'),
            'webhook_secret' => Setting::get('mercado_pago_webhook_secret'),
            'environment' => Setting::get('mercado_pago_environment', 'sandbox'),
            'payment_method' => Setting::get('mercado_pago_payment_method', 'pix_transparente'),
        ];

        return view('admin.settings.mercado-pago', compact('mpSettings'));
    }

    /**
     * Salvar configurações Mercado Pago
     */
    public function updateMercadoPago(Request $request)
    {
        $validated = $request->validate([
            'access_token' => 'required|string|min:10',
            'public_key' => 'required|string|min:10',
            'webhook_secret' => 'required|string|min:5',
            'environment' => 'required|in:sandbox,production',
            'payment_method' => 'required|in:pix_transparente,checkout_pro',
        ], [
            'access_token.required' => 'Access Token é obrigatório',
            'public_key.required' => 'Public Key é obrigatória',
            'webhook_secret.required' => 'Webhook Secret é obrigatório',
            'environment.required' => 'Ambiente é obrigatório',
            'payment_method.required' => 'Método de pagamento é obrigatório',
        ]);

        Setting::set('mercado_pago_access_token', $validated['access_token'], 'string', 'Token de acesso Mercado Pago');
        Setting::set('mercado_pago_public_key', $validated['public_key'], 'string', 'Chave pública Mercado Pago');
        Setting::set('mercado_pago_webhook_secret', $validated['webhook_secret'], 'string', 'Secret para validar webhooks');
        Setting::set('mercado_pago_environment', $validated['environment'], 'string', 'Ambiente (sandbox ou production)');
        Setting::set('mercado_pago_payment_method', $validated['payment_method'], 'string', 'Método de pagamento (pix_transparente ou checkout_pro)');

        // Sincronizar webhook_secret para .env
        $this->syncWebhookSecretToEnv($validated['webhook_secret']);

        // Invalidar cache do método de pagamento
        PaymentMethodsTrait::invalidatePaymentMethodCache();

        return redirect()->route('admin.settings.mercado-pago')
            ->with('success', '✅ Configurações Mercado Pago atualizadas com sucesso! 🔄 .env sincronizado.');
    }

    /**
     * Listar todas as configurações (opcional)
     */
    public function index()
    {
        $settings = Setting::orderBy('key')->paginate(20);

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Sincronizar webhook_secret do banco para .env
     * Quando o secret é atualizado pelo admin, este método garante que
     * o .env também seja atualizado, mantendo tudo sincronizado.
     */
    private function syncWebhookSecretToEnv(string $secret)
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            \Log::warning('Arquivo .env não encontrado em: ' . $envPath);
            return;
        }

        $env = file_get_contents($envPath);
        $line = 'MERCADO_PAGO_WEBHOOK_SECRET=' . $secret;

        if (preg_match('/^MERCADO_PAGO_WEBHOOK_SECRET=.*$/m', $env)) {
            $env = preg_replace('/^MERCADO_PAGO_WEBHOOK_SECRET=.*$/m', $line, $env);
        } else {
            $env .= PHP_EOL . $line . PHP_EOL;
        }

        file_put_contents($envPath, $env);
        \Log::info('✅ Webhook secret sincronizado para .env com sucesso');
    }
}
