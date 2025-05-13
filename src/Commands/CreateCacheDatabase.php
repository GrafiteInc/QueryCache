<?php

namespace Grafite\Cache\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class CreateCacheDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:cache-database';

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
        $this->info('Creating cache database...');

        if (! file_exists(database_path('cache.sqlite'))) {
            touch(database_path('cache.sqlite'));
        }

        \sleep(1);

         Config::set('database.connections.sqlite_cache', [
            'driver' => 'sqlite',
            'database' => config('cache.stores.sqlite.database'),
            'prefix' => config('cache.stores.sqlite.prefix'),
        ]);

        sleep(1);

        // Set the table
        app('db')->connection('sqlite_cache')->statement('CREATE TABLE cache (key STRING PRIMARY KEY, value LONGTEXT, expiration INTEGER)');

        return 0;
    }
}
