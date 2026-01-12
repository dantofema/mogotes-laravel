<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class MogotesApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?string $errorCode = null,
        private readonly ?int $httpStatus = null
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public static function fromResponse(Response $response): self
    {
        $status = $response->status();

        /** @var array{error?: array{code?: string, message?: string}}|null $body */
        $body = $response->json();

        $errorCode = $body['error']['code'] ?? null;
        $errorMessage = $body['error']['message'] ?? "Error {$status} desde Mogotes";

        return new self(
            message: is_string($errorMessage) ? $errorMessage : "Error {$status} desde Mogotes",
            errorCode: is_string($errorCode) ? $errorCode : null,
            httpStatus: $status
        );
    }
}
