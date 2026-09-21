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
     * Criar preferência de checkout (Checkout Pro - mantido para compatibilidade com Preferences API)
     * ✅ FASE 2: Validar status HTTP real
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

        // ✅ FASE 2: Validar status HTTP real
        if ($response->failed()) {
            \Illuminate\Support\Facades\Log::error('Mercado Pago Preferences API error', [
                'endpoint' => '/checkout/preferences',
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            
            throw new \Exception("Mercado Pago API error: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * ✨ NOVO: Criar Order com PIX Transparente (Orders API - RECOMENDADA)
     * 
     * Estrutura CORRIGIDA conforme documentação atual do Mercado Pago:
     * - type: 'online' (obrigatório)
     * - total_amount: número formatado com 2 decimais
     * - transactions.payments[0].payment_method.id: 'pix'
     * - transactions.payments[0].payment_method.type: 'bank_transfer'
     * - X-Idempotency-Key: UUID único por tentativa
     * 
     * Endpoint: POST /v1/orders
     * Retorna QR Code em: response['transactions']['payments'][0]['payment_method']['qr_code']
     */
    public function createOrderPix(
        int $courseId,
        string $courseTitle,
        ?string $courseDescription,
        float $amount,
        string $payerName,
        string $payerEmail
    ): array {
        $externalReference = "course_{$courseId}_" . \Illuminate\Support\Str::uuid();

        $payload = [
            'type' => 'online',                                              // ✅ OBRIGATÓRIO
            'total_amount' => number_format($amount, 2, '.', ''),            // ✅ Formato correto
            'external_reference' => $externalReference,
            'processing_mode' => 'automatic',                                // ✅ Para processamento automático
            
            // ✅ ESTRUTURA CORRIGIDA: transactions.payments (não payments no topo)
            'transactions' => [
                'payments' => [
                    [
                        'amount' => number_format($amount, 2, '.', ''),
                        'payment_method' => [
                            'id' => 'pix',                                   // ✅ Literal 'pix'
                            'type' => 'bank_transfer',                       // ✅ Para PIX
                        ],
                    ],
                ],
            ],
            
            // ✅ Informações do pagador (obrigatório para Orders API)
            'payer' => [
                'email' => $payerEmail,
            ],
        ];

        $response = Http::withToken($this->accessToken())
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Idempotency-Key' => (string) \Illuminate\Support\Str::uuid(),  // ✅ NOVO: Evita duplicação
            ])
            ->post(self::BASE_URL . '/v1/orders', $payload);

        // ✅ FASE 2: Validar status HTTP real
        if ($response->failed()) {
            \Illuminate\Support\Facades\Log::error('Mercado Pago Orders API error', [
                'endpoint' => '/v1/orders',
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            
            // Lançar exceção com status real (será capturada pelo controller)
            throw new \Exception("Mercado Pago API error: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * ✅ NOVO: Extrair QR Code da resposta da Order (Orders API)
     * 
     * Local correto: response['transactions']['payments'][0]['payment_method']
     * Contém: 'qr_code' (string) e 'qr_code_base64' (string para imagem)
     */
    public static function extractQrCodeFromOrder(array $orderResponse): ?array
    {
        // Validar estrutura
        $payment = $orderResponse['transactions']['payments'][0] ?? null;
        if (!$payment) {
            return null;
        }

        $paymentMethod = $payment['payment_method'] ?? null;
        if (!$paymentMethod) {
            return null;
        }

        // Extrair QR Code e sua versão em base64
        $qrCode = $paymentMethod['qr_code'] ?? null;
        $qrCodeBase64 = $paymentMethod['qr_code_base64'] ?? null;

        if (!$qrCode || !$qrCodeBase64) {
            return null;
        }

        return [
            'qr_code' => $qrCode,
            'qr_code_base64' => $qrCodeBase64,
        ];
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
