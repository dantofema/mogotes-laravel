<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Facades;

use Dantofema\MogotesLaravel\MogotesLaravel;
use Dantofema\MogotesLaravel\Services\FeatureFlagsService;
use Dantofema\MogotesLaravel\Services\LogsService;
use Dantofema\MogotesLaravel\Services\NotificationsService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static FeatureFlagsService feature()
 * @method static LogsService log()
 * @method static NotificationsService notifications()
 * @method static array email(string $template, string $to, array $data = [], ?string $idempotencyKey = null)
 * @method static array whatsapp(string $template, string $to, array $data = [], ?string $idempotencyKey = null)
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
