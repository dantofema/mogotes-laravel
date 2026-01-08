<?php

namespace Dantofema\MogotesLaravel\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class MogotesLogHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        // TODO: Enviar log a la API de Mogotes usando MogotesClient
    }
}
