<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel;

use Dantofema\MogotesLaravel\Commands\MogotesLaravelCommand;
use Dantofema\MogotesLaravel\Pennant\MogotesPennantDriver;
use Laravel\Pennant\Feature;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MogotesLaravelServiceProvider extends PackageServiceProvider
{
    public function packageBooted(): void
    {
        if (class_exists(Feature::class)) {
            Feature::extend('mogotes', fn($app) => new MogotesPennantDriver);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(function ($app): MogotesClient {
            /** @var string $baseUrl */
            $baseUrl = (string) config('mogotes-laravel.base_url');

            /** @var string|null $apiKey */
            $apiKey = config('mogotes-laravel.api_key');

            /** @var int $timeoutSeconds */
            $timeoutSeconds = (int) config('mogotes-laravel.timeout_seconds');

            /** @var int $connectTimeoutSeconds */
            $connectTimeoutSeconds = (int) config('mogotes-laravel.connect_timeout_seconds');

            /** @var string $userAgent */
            $userAgent = (string) config('mogotes-laravel.user_agent');

            /** @var array<string, string> $defaultHeaders */
            $defaultHeaders = (array) config('mogotes-laravel.default_headers', []);

            return new MogotesClient(
                baseUrl: $baseUrl,
                apiKey: $apiKey,
                timeoutSeconds: $timeoutSeconds,
                connectTimeoutSeconds: $connectTimeoutSeconds,
                userAgent: $userAgent,
                defaultHeaders: $defaultHeaders,
            );
        });

        $this->app->singleton(fn($app): MogotesLaravel => new MogotesLaravel($app->make(MogotesClient::class)));
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('mogotes-laravel')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_mogotes_laravel_table')
            ->hasRoutes(['api'])
            ->hasCommand(MogotesLaravelCommand::class);
    }
}
