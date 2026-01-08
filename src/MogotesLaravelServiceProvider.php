<?php

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
            Feature::extend('mogotes', function ($app) {
                return new MogotesPennantDriver;
            });
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(MogotesClient::class, function ($app) {
            return new MogotesClient(
                config('mogotes-laravel.base_url'),
                config('mogotes-laravel.api_key')
            );
        });

        $this->app->singleton(MogotesLaravel::class, function ($app) {
            return new MogotesLaravel($app->make(MogotesClient::class));
        });
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('mogotes-laravel')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_mogotes_laravel_table')
            ->hasRoutes(['api'])
            ->hasCommand(MogotesLaravelCommand::class);
    }
}
