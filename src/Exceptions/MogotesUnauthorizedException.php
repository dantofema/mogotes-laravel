<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Exceptions;

use Illuminate\Http\Client\Response;

final class MogotesUnauthorizedException extends MogotesApiException
{
    public static function fromResponse(Response $response): self
    {
        /** @var array{error?: array{code?: string, message?: string}}|null $body */
        $body = $response->json();

        $errorMessage = $body['error']['message'] ?? 'No autorizado. Verifica tu API key de Mogotes.';

        return new self(
            message: is_string($errorMessage) ? $errorMessage : 'No autorizado. Verifica tu API key de Mogotes.',
            errorCode: 'unauthorized',
            httpStatus: 401
        );
    }
}
