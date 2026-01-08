<?php

namespace Dantofema\MogotesLaravel\Facades;

use Dantofema\MogotesLaravel\MogotesLaravel;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Dantofema\MogotesLaravel\MogotesLaravel
 */
class Mogotes extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MogotesLaravel::class;
    }
}
