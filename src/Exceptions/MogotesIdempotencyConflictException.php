<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando se intenta reutilizar una idempotency_key
 * con un payload diferente al original.
 *
 * Status HTTP: 409 Conflict
 */
class MogotesIdempotencyConflictException extends Exception
{
    public function __construct(
        string $message = 'El idempotency_key ya fue usado con un payload distinto.',
        public readonly ?string $idempotencyKey = null,
        public readonly ?string $correlationId = null,
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(string $message, ?string $idempotencyKey = null, ?string $correlationId = null): self
    {
        return new self($message, $idempotencyKey, $correlationId);
    }
}
