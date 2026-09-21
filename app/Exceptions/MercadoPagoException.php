<?php

namespace App\Exceptions;

class MercadoPagoException extends \Exception
{
    const TYPE_UNAUTHORIZED = 'unauthorized';
    const TYPE_INVALID_TOKEN = 'invalid_token';
    const TYPE_TIMEOUT = 'timeout';
    const TYPE_RATE_LIMIT = 'rate_limit';
    const TYPE_INVALID_RESPONSE = 'invalid_response';
    const TYPE_CONNECTION_ERROR = 'connection_error';

    public function __construct(
        public string $type,
        public mixed $response = null,
        $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($this->getDefaultMessage(), $code, $previous);
    }

    public function getDefaultMessage(): string
    {
        return match ($this->type) {
            self::TYPE_UNAUTHORIZED => 'Acesso negado ao Mercado Pago (verifique token)',
            self::TYPE_INVALID_TOKEN => 'Token de acesso inválido ou expirado',
            self::TYPE_TIMEOUT => 'Timeout ao conectar ao Mercado Pago (tente novamente)',
            self::TYPE_RATE_LIMIT => 'Muitas requisições (aguarde alguns minutos)',
            self::TYPE_INVALID_RESPONSE => 'Resposta inválida do Mercado Pago',
            self::TYPE_CONNECTION_ERROR => 'Erro de conexão com Mercado Pago',
            default => 'Erro ao processar pagamento',
        };
    }

    public function getHttpStatusCode(): int
    {
        return match ($this->type) {
            self::TYPE_UNAUTHORIZED, self::TYPE_INVALID_TOKEN => 401,
            self::TYPE_RATE_LIMIT => 429,
            self::TYPE_TIMEOUT => 504,
            self::TYPE_CONNECTION_ERROR => 503,
            default => 500,
        };
    }

    public function toJson(): array
    {
        return [
            'error' => $this->type,
            'message' => $this->getDefaultMessage(),
        ];
    }
}
