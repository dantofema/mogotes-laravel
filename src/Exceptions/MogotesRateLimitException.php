<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Exceptions;

use Illuminate\Http\Client\Response;

final class MogotesRateLimitException extends MogotesApiException
{
    public function __construct(
        string $message,
        private readonly int $retryAfterSeconds = 60
    ) {
        parent::__construct($message, 'rate_limit_exceeded', 429);
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }

    public static function fromResponse(Response $response): self
    {
        /** @var array{error?: array{message?: string}}|null $body */
        $body = $response->json();

        $errorMessage = $body['error']['message'] ?? 'Límite de tasa excedido. Intenta nuevamente más tarde.';

        $retryAfter = $response->header('Retry-After');
        $retryAfterSeconds = is_string($retryAfter) ? (int) $retryAfter : 60;

        return new self(
            message: is_string($errorMessage) ? $errorMessage : 'Límite de tasa excedido. Intenta nuevamente más tarde.',
            retryAfterSeconds: max(1, $retryAfterSeconds)
        );
    }
}
