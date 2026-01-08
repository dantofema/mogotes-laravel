<?php

namespace Dantofema\MogotesLaravel\Logging;

use Monolog\Logger;

class MogotesLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array<string, mixed>  $config
     */
    public function __invoke(array $config): Logger
    {
        return new Logger('mogotes', [
            new MogotesLogHandler($config['level'] ?? 'debug'),
        ]);
    }
}
