<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MemoizationTest extends TestCase
{
    /**
     * With memoization on, a second identical query is served from the
     * in-request L1 store even after the backend cache has been wiped,
     * proving it never touched the database or the cache backend.
     *
     * @dataProvider strictModeContextProvider
     */
    public function test_repeated_query_is_served_from_memory()
    {
        config()->set('query-cache.memoize', true);

        factory(Post::class, 3)->create();

        $first = Post::get();

        // Wipe the L2 backend only. The L1 (request-scoped) store is held in
        // the container, not the cache backend, so it survives this.
        Cache::flush();

        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        $second = Post::get();

        $this->assertCount(0, DB::connection()->getQueryLog());
        $this->assertEquals($first->pluck('id'), $second->pluck('id'));
    }

    /**
     * With memoization off, the same scenario must fall through to the
     * database because there is no L1 store to serve the repeated query.
     *
     * @dataProvider strictModeContextProvider
     */
    public function test_repeated_query_hits_database_when_memoization_disabled()
    {
        config()->set('query-cache.memoize', false);

        factory(Post::class, 3)->create();

        Post::get();

        Cache::flush();

        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        Post::get();

        $this->assertNotEmpty(DB::connection()->getQueryLog());
    }

    /**
     * Flushing the query cache must also clear the L1 store so a write
     * followed by a read in the same request cannot serve a stale value.
     *
     * @dataProvider strictModeContextProvider
     */
    public function test_flush_clears_the_memoization_store()
    {
        config()->set('query-cache.memoize', true);

        factory(Post::class, 3)->create();

        Post::get();

        // A model write triggers the observer, which flushes the cache and,
        // with it, the memoization store.
        factory(Post::class)->create();

        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        $afterWrite = Post::get();

        $this->assertNotEmpty(DB::connection()->getQueryLog());
        $this->assertCount(4, $afterWrite);
    }
}
