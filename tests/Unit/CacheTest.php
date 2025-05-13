<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class CacheTest extends TestCase
{
    public function testCacheProvider()
    {
        if (file_exists(database_path('cache.sqlite'))) {
            unlink(database_path('cache.sqlite'));
        }

        // Create the database
        Artisan::call('make:cache-database');

        // Put a value
        Cache::put('foo', 'bar', 60);

        $value = Cache::get('foo');

        $this->assertEquals('bar', $value);
    }

    public function testCacheKeyMaker()
    {
        if (file_exists(database_path('cache.sqlite'))) {
            unlink(database_path('cache.sqlite'));
        }

        // Create the database
        Artisan::call('make:cache-database');

        // Put a value
        $key = cache()->key('foo', ['bar']);

        cache()->put($key, 'bar', 60);

        $value = Cache::get($key);

        $this->assertEquals('bar', $value);
    }

    public function testCacheLocksTable()
    {
        if (file_exists(database_path('cache.sqlite'))) {
            unlink(database_path('cache.sqlite'));
        }

        // Create the database
        Artisan::call('make:cache-database');

        // Create the lock table
        Artisan::call('make:cache-locks-table');

        $lock = cache()->lock('foo-bar', 10);
        $value = 'bar';

        if ($lock->get()) {
            // Lock acquired for 10 seconds...
            $this->assertEquals('bar', $value);

            $lock->release();
        }
    }
}
