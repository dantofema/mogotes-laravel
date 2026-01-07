<?php

namespace Dantofema\MogotesLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Dantofema\MogotesLaravel\MogotesLaravel
 */
class MogotesLaravel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Dantofema\MogotesLaravel\MogotesLaravel::class;
    }
}
