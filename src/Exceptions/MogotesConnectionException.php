<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Exceptions;

use RuntimeException;

final class MogotesConnectionException extends RuntimeException
{
    public static function timeout(string $message = ''): self
    {
        $defaultMessage = 'El servidor de Mogotes no respondió dentro del tiempo límite';

        return new self($message !== '' ? $message : $defaultMessage);
    }

    public static function unreachable(string $message = ''): self
    {
        $defaultMessage = 'No se pudo conectar con el servidor de Mogotes';

        return new self($message !== '' ? $message : $defaultMessage);
    }
}
