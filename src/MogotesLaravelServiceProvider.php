<?php

namespace Dantofema\MogotesLaravel;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dantofema\MogotesLaravel\Commands\MogotesLaravelCommand;

class MogotesLaravelServiceProvider extends PackageServiceProvider
{
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
            ->hasCommand(MogotesLaravelCommand::class);
    }
}
