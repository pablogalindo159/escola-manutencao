<?php

namespace App\Traits;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

trait PaymentMethodsTrait
{
    /**
     * Obter método de pagamento com cache
     */
    public function getPaymentMethod(): string
    {
        return Cache::remember('payment_method', 3600, function () {
            return Setting::query()
                ->where('key', 'mercado_pago_payment_method')
                ->value('value') ?? 'pix_transparente';
        });
    }

    /**
     * Verificar se é PIX Transparente
     */
    public function isPixTransparent(): bool
    {
        return $this->getPaymentMethod() === 'pix_transparente';
    }

    /**
     * Verificar se é Checkout Pro
     */
    public function isCheckoutPro(): bool
    {
        return $this->getPaymentMethod() === 'checkout_pro';
    }

    /**
     * Invalidar cache do método de pagamento
     */
    public static function invalidatePaymentMethodCache(): void
    {
        Cache::forget('payment_method');
    }
}
