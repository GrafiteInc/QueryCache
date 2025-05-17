<?php

namespace Grafite\QueryCache\Commands;

use Illuminate\Console\Command;

class QueryCachePurgeTag extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'query-cache:purge-tag';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // app('db')->connection('sqlite_cache')->statement('delete from cache_locks;');

        // $this->info('Purged cache_locks table...');

        return 0;
    }
}
