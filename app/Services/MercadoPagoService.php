<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fala diretamente com a API REST do Mercado Pago (Checkout Pro).
 * Não usa o SDK oficial (mercadopago/dx-php) de propósito - evita
 * depender de composer install de um pacote novo pra fazer o deploy
 * funcionar; é só um POST/GET autenticado com Bearer token, igual já
 * fazemos com a oEmbed do YouTube neste projeto.
 *
 * Documentação: https://www.mercadopago.com.br/developers/pt/docs/checkout-pro/landing
 */
class MercadoPagoService
{
    private const BASE_URL = 'https://api.mercadopago.com';

    private function accessToken(): string
    {
        // Tenta primeiro o banco de dados (painel admin), depois o .env
        $token = \App\Models\Setting::get('mercado_pago_access_token');
        
        if (!$token) {
            $token = config('services.mercadopago.access_token');
        }

        if (!$token) {
            throw new \RuntimeException(
                '❌ MERCADO_PAGO_ACCESS_TOKEN não configurado. Configure no Painel Admin > Configurações > Mercado Pago'
            );
        }

        return $token;
    }

    /**
     * Obter a Public Key do Mercado Pago
     */
    private function publicKey(): string
    {
        // Tenta primeiro o banco de dados, depois o .env
        $key = \App\Models\Setting::get('mercado_pago_public_key');
        
        if (!$key) {
            $key = config('services.mercadopago.public_key');
        }

        if (!$key) {
            throw new \RuntimeException(
                '❌ MERCADO_PAGO_PUBLIC_KEY não configurada. Configure no Painel Admin > Configurações > Mercado Pago'
            );
        }

        return $key;
    }

    /**
     * Cria uma "preference" (sessão de checkout) pro curso, e devolve o
     * array de resposta do Mercado Pago (incluindo 'id' e 'init_point',
     * a URL da página de pagamento hospedada por eles).
     */
    public function createPreference(Course $course, User $user): array
    {
        $externalReference = "course_{$course->id}_user_{$user->id}";

        $payload = [
            'items' => [[
                'title' => $course->title,
                'description' => \Illuminate\Support\Str::limit($course->description ?? '', 200),
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => (float) $course->price,
            ]],
            'payer' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'back_urls' => [
                'success' => route('checkout.return', ['status' => 'success']),
                'pending' => route('checkout.return', ['status' => 'pending']),
                'failure' => route('checkout.return', ['status' => 'failure']),
            ],
            'auto_return' => 'approved',
            'notification_url' => route('webhooks.mercadopago'),
            'external_reference' => $externalReference,
            'statement_descriptor' => 'ESCOLA MANUTENCAO',
        ];

        $response = Http::withToken($this->accessToken())
            ->timeout(15)
            ->post(self::BASE_URL . '/checkout/preferences', $payload);

        if (!$response->successful()) {
            Log::error('Mercado Pago: falha ao criar preferência', [
                'course_id' => $course->id,
                'user_id' => $user->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Não foi possível iniciar o pagamento. Tente novamente em instantes.');
        }

        return $response->json();
    }

    /**
     * Busca os dados completos de um pagamento pelo ID (usado no
     * webhook - nunca confiamos só no payload que o Mercado Pago manda
     * na notificação, sempre confirmamos direto na API deles antes de
     * liberar acesso ao curso).
     */
    public function getPayment(string $paymentId): array
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(15)
            ->get(self::BASE_URL . "/v1/payments/{$paymentId}");

        if (!$response->successful()) {
            Log::error('Mercado Pago: falha ao consultar pagamento', [
                'payment_id' => $paymentId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Não foi possível consultar o pagamento {$paymentId} no Mercado Pago.");
        }

        return $response->json();
    }

    /**
     * Extrai course_id e user_id do external_reference que a gente
     * mesmo gerou em createPreference(). Retorna null se o formato não
     * bater (nunca deveria acontecer com pagamentos criados por nós).
     */
    public function parseExternalReference(?string $externalReference): ?array
    {
        if (!$externalReference || !preg_match('/^course_(\d+)_user_(\d+)$/', $externalReference, $m)) {
            return null;
        }

        return [
            'course_id' => (int) $m[1],
            'user_id' => (int) $m[2],
        ];
    }

    /**
     * Mapeia o payment_type_id do Mercado Pago pro enum que usamos na
     * coluna payments.method.
     */
    public function mapPaymentMethod(?string $paymentTypeId): ?string
    {
        return match ($paymentTypeId) {
            'credit_card' => 'credit_card',
            'debit_card' => 'debit_card',
            'bank_transfer', 'pix' => 'pix',
            'ticket' => 'boleto',
            'account_money', 'digital_wallet' => 'wallet',
            default => null,
        };
    }

    /**
     * Cria um pagamento PIX Transparente (sem redirecionamento)
     * Retorna QR Code, código de cópia e cola, URL do comprovante, etc.
     */
    public function createPixPayment(Course $course, User $user): array
    {
        $externalReference = "course_{$course->id}_user_{$user->id}";

        $payload = [
            'transaction_amount' => (float) $course->price,
            'description' => $course->title,
            'payment_method_id' => 'pix',
            'payer' => [
                'name' => $user->name,
                'email' => $user->email,
                'identification' => [
                    'type' => 'CPF',
                    'number' => $user->cpf ?? '00000000000', // Campo opcional
                ],
            ],
            'notification_url' => route('webhooks.mercadopago'),
            'external_reference' => $externalReference,
        ];

        $response = Http::withToken($this->accessToken())
            ->timeout(15)
            ->post(self::BASE_URL . '/v1/payments', $payload);

        if (!$response->successful()) {
            Log::error('Mercado Pago PIX: falha ao criar pagamento', [
                'course_id' => $course->id,
                'user_id' => $user->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException(
                'Não foi possível gerar o QR Code PIX. Verifique suas credenciais no Painel Admin.'
            );
        }

        $data = $response->json();

        // Extrai os dados do PIX
        return [
            'id' => $data['id'] ?? null,
            'qr_code' => $data['point_of_interaction']['transaction_data']['qr_code'] ?? null,
            'qr_code_base64' => $data['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
            'pix_copy_paste' => $data['point_of_interaction']['transaction_data']['copy_and_paste'] ?? null,
            'ticket_url' => $data['transaction_details']['ticket_url'] ?? null,
            'status' => $data['status'] ?? null,
            'date_of_expiration' => $data['date_of_expiration'] ?? null,
        ];
    }

    /**
     * Consultar status de um pagamento PIX
     */
    public function getPixStatus(string $paymentId): array
    {
        $response = Http::withToken($this->accessToken())
            ->timeout(15)
            ->get(self::BASE_URL . "/v1/payments/{$paymentId}");

        if (!$response->successful()) {
            Log::error('Mercado Pago: falha ao consultar status PIX', [
                'payment_id' => $paymentId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Não foi possível consultar o status do pagamento.");
        }

        $data = $response->json();

        return [
            'id' => $data['id'] ?? null,
            'status' => $data['status'] ?? null,
            'status_detail' => $data['status_detail'] ?? null,
        ];
    }
}
