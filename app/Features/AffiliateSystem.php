<?php

namespace App\Features;

use App\Models\User;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Sistema de Afiliados - Programa de referência e comissões
 */
class AffiliateSystem
{
    // Configurações de comissão
    const COMMISSION_RATES = [
        'level_1' => 0.10, // 10%
        'level_2' => 0.05, // 5%
        'level_3' => 0.02, // 2%
    ];

    /**
     * Criar novo afiliado
     */
    public static function createAffiliate(User $user): Affiliate
    {
        $affiliate = Affiliate::updateOrCreate(
            ['user_id' => $user->id],
            [
                'referral_code' => self::generateReferralCode(),
                'status' => 'active',
                'commission_rate' => self::COMMISSION_RATES['level_1'],
            ]
        );

        return $affiliate;
    }

    /**
     * Gerar código de referência único
     */
    private static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Affiliate::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Registrar novo referido via código
     */
    public static function registerReferral(User $referrer, User $referred): void
    {
        $referrer->affiliate()->referrals()->attach($referred->id);
    }

    /**
     * Calcular comissão quando pagamento é confirmado
     */
    public static function processPaymentCommission(Payment $payment): void
    {
        $user = $payment->user;
        
        // Verificar se foi registrado via referral
        $referrer = Affiliate::whereHas('referrals', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })->first();

        if (!$referrer) return;

        // Comissão de nível 1 (direto)
        $commission_amount = $payment->amount * self::COMMISSION_RATES['level_1'];

        AffiliateCommission::create([
            'affiliate_id' => $referrer->id,
            'payment_id' => $payment->id,
            'referred_user_id' => $user->id,
            'commission_level' => 1,
            'commission_amount' => $commission_amount,
            'status' => 'pending',
        ]);

        // Comissões de nível 2 e 3 (cascata)
        if ($referrer->referrer_id) {
            self::processL2Commission($referrer, $payment);
        }

        if ($referrer->referrer && $referrer->referrer->referrer_id) {
            self::processL3Commission($referrer, $payment);
        }
    }

    /**
     * Processar comissão de nível 2
     */
    private static function processL2Commission($referrer, $payment): void
    {
        $commission_amount = $payment->amount * self::COMMISSION_RATES['level_2'];

        AffiliateCommission::create([
            'affiliate_id' => $referrer->referrer_id,
            'payment_id' => $payment->id,
            'referred_user_id' => $payment->user_id,
            'commission_level' => 2,
            'commission_amount' => $commission_amount,
            'status' => 'pending',
        ]);
    }

    /**
     * Processar comissão de nível 3
     */
    private static function processL3Commission($referrer, $payment): void
    {
        $commission_amount = $payment->amount * self::COMMISSION_RATES['level_3'];

        AffiliateCommission::create([
            'affiliate_id' => $referrer->referrer->referrer_id,
            'payment_id' => $payment->id,
            'referred_user_id' => $payment->user_id,
            'commission_level' => 3,
            'commission_amount' => $commission_amount,
            'status' => 'pending',
        ]);
    }

    /**
     * Obter dashboard do afiliado
     */
    public static function getAffiliateDashboard(User $user): array
    {
        $affiliate = $user->affiliate;

        if (!$affiliate) {
            return [];
        }

        $referrals = $affiliate->referrals()->count();
        $active_referrals = $affiliate->referrals()
            ->where('last_login', '>', now()->subDays(30))
            ->count();

        $total_commission = $affiliate->commissions()
            ->sum('commission_amount');

        $pending_commission = $affiliate->commissions()
            ->where('status', 'pending')
            ->sum('commission_amount');

        $paid_commission = $affiliate->commissions()
            ->where('status', 'paid')
            ->sum('commission_amount');

        // Comissões por mês
        $monthly_commissions = $affiliate->commissions()
            ->selectRaw('DATE_TRUNC(\'month\', created_at) as month')
            ->selectRaw('SUM(commission_amount) as total')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->take(12)
            ->get();

        return [
            'referral_code' => $affiliate->referral_code,
            'commission_rate' => $affiliate->commission_rate,
            'total_referrals' => $referrals,
            'active_referrals' => $active_referrals,
            'total_commission' => $total_commission,
            'pending_commission' => $pending_commission,
            'paid_commission' => $paid_commission,
            'monthly_commissions' => $monthly_commissions,
            'referral_link' => config('app.url') . "?ref={$affiliate->referral_code}",
        ];
    }

    /**
     * Pagar comissões pendentes
     */
    public static function payoutCommissions(User $user): void
    {
        $affiliate = $user->affiliate;
        $pending = $affiliate->commissions()
            ->where('status', 'pending')
            ->sum('commission_amount');

        if ($pending <= 0) return;

        // Processar pagamento
        // TODO: Integração com método de pagamento

        // Marcar como pago
        $affiliate->commissions()
            ->where('status', 'pending')
            ->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

        // Notificar
        $user->notify(new CommissionPaidNotification($pending));
    }

    /**
     * Gerar relatório de afiliados
     */
    public static function generateAffiliateReport(): array
    {
        return Affiliate::with('user', 'commissions')
            ->selectRaw('affiliates.*, COUNT(DISTINCT referred_user_id) as total_referrals')
            ->selectRaw('SUM(commission_amount) as total_earned')
            ->groupBy('affiliates.id')
            ->orderByDesc('total_earned')
            ->get()
            ->map(function ($affiliate) {
                return [
                    'id' => $affiliate->id,
                    'name' => $affiliate->user->name,
                    'email' => $affiliate->user->email,
                    'code' => $affiliate->referral_code,
                    'referrals' => $affiliate->total_referrals,
                    'total_earned' => $affiliate->total_earned,
                    'status' => $affiliate->status,
                    'created_at' => $affiliate->created_at,
                ];
            })
            ->toArray();
    }
}
