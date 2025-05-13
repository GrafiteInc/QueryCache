<?php

namespace Grafite\Cache\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class CreateCacheLocksTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:cache-locks-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a cache database.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Adding cache_locks table...');

        Config::set('database.connections.sqlite_cache', [
            'driver' => 'sqlite',
            'database' => config('cache.stores.sqlite.database'),
            'prefix' => config('cache.stores.sqlite.prefix'),
        ]);

        sleep(1);

        if (file_exists(config('cache.stores.sqlite.database'))) {
            app('db')->connection('sqlite_cache')->statement('CREATE TABLE cache_locks (key STRING PRIMARY KEY, owner STRING, expiration INTEGER)');
        }

        return 0;
    }
}
