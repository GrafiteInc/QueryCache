<?php

namespace Grafite\QueryCache;

use Illuminate\Support\ServiceProvider;

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
            __DIR__.'/../config/query-cache.php' => base_path('config/query-cache.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // $this->commands([
        //     QueryCachePurgeTag::class,
        // ]);
    }
}
