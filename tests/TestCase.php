<?php

namespace Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected $app;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('cache.stores.sqlite', [
            'driver' => 'sqlite',
            'table' => 'cache',
            'database' => database_path('cache.sqlite'),
            'prefix' => '',
            'encrypted' => true,
        ]);

        $app->make('Illuminate\Contracts\Http\Kernel');
    }

    protected function getPackageProviders($app)
    {
        return [
            \Grafite\Cache\CacheProvider::class,
        ];
    }

    public function setUp(): void
    {
        parent::setUp();

        app('config')->set('cache.default', 'sqlite');

        $this->withoutMiddleware();
    }
}
