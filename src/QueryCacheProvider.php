<?php

namespace Grafite\Cache;

use Exception;
use Grafite\Cache\Stores\SqliteStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Grafite\Cache\Commands\CreateCacheDatabase;
use Grafite\Cache\Commands\CreateCacheLocksTable;

class QueryCacheProvider extends ServiceProvider
{
    /**
     * Boot method.
     *
     * @return void
     */
    public function boot()
    {
         $this->publishes([
            dirname(__DIR__).'/config/query-cache.php' => config_path('query-cache.php'),
        ], 'grafite-query-cache');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // $this->commands([
        //     CreateCacheDatabase::class,
        //     CreateCacheLocksTable::class,
        // ]);
    }
}
