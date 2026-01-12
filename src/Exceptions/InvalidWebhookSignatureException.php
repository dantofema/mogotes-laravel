<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando la firma HMAC del webhook no es válida.
 */
class InvalidWebhookSignatureException extends Exception
{
    public static function signatureMismatch(): self
    {
        return new self('La firma del webhook no coincide con la esperada.');
    }

    public static function missingSignature(): self
    {
        return new self('El webhook no contiene la firma requerida en el header Mogotes-Signature.');
    }

    public static function missingTimestamp(): self
    {
        return new self('El webhook no contiene el timestamp requerido en el header Mogotes-Timestamp.');
    }

    public static function timestampOutOfWindow(int $timestamp, int $maxAge): self
    {
        return new self(sprintf(
            'El timestamp del webhook (%d) está fuera de la ventana permitida de %d segundos.',
            $timestamp,
            $maxAge
        ));
    }
}
