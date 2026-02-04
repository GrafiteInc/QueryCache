<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Post;

class MemoTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_memo_cache_is_used()
    {
        if (! method_exists(cache(), 'memo')) {
            $this->markTestSkipped('Memo cache is not available.');
        }

        $post = factory(Post::class)->create();
        $storedPosts = Post::get();
        $tagKey = sha1(cache()->get('tag:Grafite\QueryCache\Test\Models\Post:key'));
        $cacheKey = $tagKey.':qc:sqlitegetselect * from "posts"a:0:{}';

        // Check that the memoized cache has the key
        $this->assertTrue(cache()->memo()->has($cacheKey));

        // Get the memoized value
        $memoizedValue = cache()->memo()->get($cacheKey);
        $this->assertNotNull($memoizedValue);

        $this->assertEquals(
            $memoizedValue->first()->id,
            $storedPosts->first()->id
        );

        $this->assertEquals(
            $memoizedValue->first()->id,
            $post->id
        );
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_memo_cache_count()
    {
        if (! method_exists(cache(), 'memo')) {
            $this->markTestSkipped('Memo cache is not available.');
        }

        factory(Post::class, 5)->create();
        $postsCount = Post::query()->count();
        $tagKey = sha1(cache()->get('tag:Grafite\QueryCache\Test\Models\Post:key'));
        $cacheKey = $tagKey.':qc:sqlitegetselect count(*) as aggregate from "posts"a:0:{}';

        // Check that the memoized cache has the key
        $this->assertTrue(cache()->memo()->has($cacheKey));

        // Get the memoized value and verify it matches
        $memoizedValue = cache()->memo()->get($cacheKey);
        $this->assertNotNull($memoizedValue);

        $this->assertEquals(
            $memoizedValue->first()->aggregate,
            $postsCount
        );
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_memo_cache_first()
    {
        if (! method_exists(cache(), 'memo')) {
            $this->markTestSkipped('Memo cache is not available.');
        }

        $post = factory(Post::class)->create();
        $storedPost = Post::first();
        $tagKey = sha1(cache()->get('tag:Grafite\QueryCache\Test\Models\Post:key'));
        $cacheKey = $tagKey.':qc:sqlitegetselect * from "posts" limit 1a:0:{}';

        // Check that the memoized cache has the key
        $this->assertTrue(cache()->memo()->has($cacheKey));

        // Get the memoized value
        $memoizedValue = cache()->memo()->get($cacheKey);
        $this->assertNotNull($memoizedValue);

        $this->assertEquals(
            $memoizedValue->first()->id,
            $storedPost->id
        );

        $this->assertEquals(
            $memoizedValue->first()->id,
            $post->id
        );
    }
}
