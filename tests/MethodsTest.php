<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Post;
use Illuminate\Support\Facades\Cache;

class MethodsTest extends TestCase
{
    public function test_do_not_cache()
    {
        $post = factory(Post::class)->create();

        $storedPost = Post::doNotCache()->first();
        $tagKey = sha1(cache()->get('tag:Grafite\QueryCache\Test\Models\Post:key'));
        $cache = Cache::get($tagKey.':qc:sqlitegetselect * from "posts" limit 1a:0:{}');
        $this->assertNull($cache);

        $storedPost = Post::dontCache()->first();
        $tagKey = sha1(cache()->get('tag:Grafite\QueryCache\Test\Models\Post:key'));
        $cache = Cache::get($tagKey.':qc:sqlitegetselect * from "posts" limit 1a:0:{}');
        $this->assertNull($cache);
    }

    public function test_cache_prefix()
    {
        $post = factory(Post::class)->create();
        $storedPost = Post::cacheFor(now()->addHours(1))->first();
        $tagKey = sha1(cache()->get('tag:Grafite\QueryCache\Test\Models\Post:key'));
        $cache = Cache::get($tagKey.':qc:sqlitegetselect * from "posts" limit 1a:0:{}');

        $this->assertNotNull($cache);
    }

    public function test_cache_tags()
    {
        $post = factory(Post::class)->create();
        $storedPost = Post::cacheFor(now()->addHours(1))->first();
        $baseTag = 'Grafite\QueryCache\Test\Models\Post';
        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "posts" limit 1a:0:{}', ['test']);

        // The caches that do not support tagging should
        // cache the query either way.
        $this->driverSupportsTags()
            ? $this->assertNull($cache)
            : $this->assertNotNull($cache);

        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "posts" limit 1a:0:{}', [$baseTag]);
        $this->assertNotNull($cache);
    }

    public function test_cache_flush_with_the_right_tag()
    {
        $post = factory(Post::class)->create();
        $baseTag = 'Grafite\QueryCache\Test\Models\Post';
        $storedPost = Post::cacheFor(now()->addHours(1))->first();

        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "posts" limit 1a:0:{}', [$baseTag]);
        $this->assertNotNull($cache);

        Post::flushQueryCache([$baseTag]);

        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "posts" limit 1a:0:{}', [$baseTag]);
        $this->assertNull($cache);
    }

    public function test_cache_flush_without_the_right_tag()
    {
        $post = factory(Post::class)->create();
        $baseTag = 'Grafite\QueryCache\Test\Models\Post';
        $storedPost = Post::cacheFor(now()->addHours(1))->first();

        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "posts" limit 1a:0:{}', [$baseTag]);
        $this->assertNotNull($cache);

        Post::flushQueryCache(['test2']);
        Post::flushQueryCacheWithTag('test2');

        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "posts" limit 1a:0:{}', [$baseTag]);

        // The caches that do not support tagging should
        // flush the cache either way since tags are not supported.
        $this->driverSupportsTags()
            ? $this->assertNotNull($cache)
            : $this->assertNull($cache);
    }
}
