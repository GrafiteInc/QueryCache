<?php

namespace Grafite\Cache\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class PurgeCacheLocksTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:purge-locks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge the cache_locks table.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Config::set('database.connections.sqlite_cache', [
            'driver' => 'sqlite',
            'database' => config('cache.stores.sqlite.database'),
            'prefix' => config('cache.stores.sqlite.prefix'),
        ]);

        sleep(1);

        if (file_exists(config('cache.stores.sqlite.database'))) {
            app('db')->connection('sqlite_cache')->statement('delete from cache_locks;');
        }

        $this->info('Purged cache_locks table...');

        return 0;
    }
}
