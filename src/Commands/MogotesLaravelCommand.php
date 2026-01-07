<?php

namespace Dantofema\MogotesLaravel\Commands;

use Illuminate\Console\Command;

class MogotesLaravelCommand extends Command
{
    public $signature = 'mogotes-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
