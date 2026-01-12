<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Facades;

use Dantofema\MogotesLaravel\MogotesLaravel;
use Dantofema\MogotesLaravel\Services\FeatureFlagsService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static FeatureFlagsService feature()
 *
 * @see \Dantofema\MogotesLaravel\MogotesLaravel
 */
class Mogotes extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MogotesLaravel::class;
    }
}
