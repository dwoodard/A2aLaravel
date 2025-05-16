<?php

namespace Dwoodard\A2aLaravel\Tests;

use Dwoodard\A2aLaravel\A2aLaravelServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(
            __DIR__.'/../../packages/dwoodard/A2aLaravel/database/migrations'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            A2aLaravelServiceProvider::class,
        ];
    }
}
