<?php

declare(strict_types=1);

namespace Dantofema\MogotesLaravel\Tests;

use Dantofema\MogotesLaravel\MogotesLaravelServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Pennant\Feature;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Dantofema\\Mogotes\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        if (class_exists(Feature::class)) {
            Feature::forgetDrivers();
            Feature::setDefaultDriver('mogotes');
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            MogotesLaravelServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        config()->set('cache.default', 'array');

        config()->set('pennant.default', 'mogotes');
        config()->set('pennant.stores.mogotes', [
            'driver' => 'mogotes',
        ]);

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
