<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Exceptions;

use RuntimeException;

final class MogotesMisconfiguredException extends RuntimeException
{
    public static function missingApiKey(): self
    {
        return new self('La API key de Mogotes no está configurada. Configurá MOGOTES_API_KEY en el .env o en el config.');
    }
}
