<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        return redirect()->route('admin.settings.mercado-pago')
            ->with('success', '✅ Configurações Mercado Pago atualizadas com sucesso!');
    }

    /**
     * Listar todas as configurações (opcional)
     */
    public function index()
    {
        $settings = Setting::orderBy('key')->paginate(20);

        return view('admin.settings.index', compact('settings'));
    }
}
