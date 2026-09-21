<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MercadoPagoService
{
    private const BASE_URL = 'https://api.mercadopago.com';

    /**
     * Obter Access Token das configurações
     * Prioridade: 1º banco de dados (Admin) → 2º .env
     */
    public function accessToken(): string
    {
        return \App\Models\Setting::get(
            'mercado_pago_access_token',
            config('services.mercadopago.access_token', '')
        );
    }

    /**
     * Criar preferência de checkout (Checkout Pro - mantido para compatibilidade)
     */
    public function createPreference(array $data): array
    {
        $payload = [
            'items' => [
                [
                    'id' => $data['course_id'] ?? 'course_' . time(),
                    'title' => $data['title'] ?? 'Curso',
                    'description' => $data['description'] ?? '',
                    'picture_url' => $data['picture_url'] ?? null,
                    'category_id' => 'courses',
                    'quantity' => 1,
                    'currency_id' => 'BRL',
                    'unit_price' => (float) $data['amount'],
                ]
            ],
            'payer' => [
                'name' => $data['payer_name'] ?? 'Cliente',
                'email' => $data['payer_email'] ?? 'noemail@example.com',
            ],
            'back_urls' => [
                'success' => $data['success_url'] ?? route('student.checkout.return'),
                'failure' => $data['failure_url'] ?? route('student.checkout.return'),
                'pending' => $data['pending_url'] ?? route('student.checkout.return'),
            ],
            'auto_return' => 'approved',
            'external_reference' => $data['external_reference'] ?? 'ref_' . time(),
            'notification_url' => route('webhooks.mercadopago'),
            'binary_mode' => true,
        ];

        $response = Http::withToken($this->accessToken())
            ->post(self::BASE_URL . '/checkout/preferences', $payload);

        return $response->json();
    }

    /**
     * ✨ NOVO: Criar Order com PIX Transparente (Orders API - RECOMENDADA)
     * 
     * Endpoint: POST /v1/orders
     * Retorna QR Code no response.payments[0].transaction_data
     */
    public function createOrderPix(
        int $courseId,
        string $courseTitle,
        ?string $courseDescription,
        float $amount,
        string $payerName,
        string $payerEmail
    ): array {
        $externalReference = "course_{$courseId}_" . time();

        $payload = [
            // Informações da ordem
            'total_amount' => (float) $amount,
            'currency' => 'BRL',
            'description' => 'Compra de curso na Escola da Manutenção',

            // Itens da ordem
            'items' => [
                [
                    'sku_number' => (string) $courseId,
                    'category' => 'courses',
                    'title' => $courseTitle,
                    'description' => $courseDescription
                        ? substr($courseDescription, 0, 200)
                        : 'Curso profissional de manutenção',
                    'quantity' => 1,
                    'unit_price' => (float) $amount,
                ]
            ],

            // Informações do pagador
            'payer' => [
                'name' => $payerName,
                'email' => $payerEmail,
            ],

            // Configuração de pagamento (PIX)
            'payments' => [
                [
                    'type' => 'wallet_purchase',
                    'additional_info' => [
                        'external_reference' => $externalReference,
                    ],
                ]
            ],

            // URLs de callback
            'notification_url' => route('webhooks.mercadopago'),
            'back_urls' => [
                'success' => route('student.checkout.return', ['status' => 'approved']),
                'failure' => route('student.checkout.return', ['status' => 'failure']),
            ],
        ];

        $response = Http::withToken($this->accessToken())
            ->post(self::BASE_URL . '/v1/orders', $payload);

        return $response->json();
    }

    /**
     * ✨ NOVO: Obter status de uma Order pelo ID
     */
    public function getOrderStatus(string $orderId): array
    {
        $response = Http::withToken($this->accessToken())
            ->get(self::BASE_URL . "/v1/orders/{$orderId}");

        return $response->json();
    }

    /**
     * Obter status de um pagamento (mantido para compatibilidade com Payments API)
     */
    public function getPaymentStatus(string $paymentId): array
    {
        $response = Http::withToken($this->accessToken())
            ->get(self::BASE_URL . "/v1/payments/{$paymentId}");

        return $response->json();
    }

    /**
     * Extrair QR Code do response da Order
     */
    public static function extractQrCodeFromOrder(array $orderResponse): ?array
    {
        // Estrutura esperada: payments[0].transaction_data.qr_code
        $payment = $orderResponse['payments'][0] ?? null;
        if (!$payment) {
            return null;
        }

        $transactionData = $payment['transaction_data'] ?? null;
        if (!$transactionData) {
            return null;
        }

        return [
            'qr_code' => $transactionData['qr_code'] ?? null,
            'qr_code_base64' => $transactionData['qr_code_base64'] ?? null,
        ];
    }

    /**
     * Extrair QR Code do response do pagamento (Legacy Payments API)
     */
    public static function extractQrCodeFromPayment(array $paymentResponse): ?array
    {
        $transactionData = $paymentResponse['point_of_interaction']['transaction_data'] ?? null;
        if (!$transactionData) {
            return null;
        }

        return [
            'qr_code' => $transactionData['qr_code'] ?? null,
            'qr_code_base64' => $transactionData['qr_code_base64'] ?? null,
        ];
    }
}
