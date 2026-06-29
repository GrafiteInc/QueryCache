<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Post;

class PreventStampedeTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_query_is_cached_when_stampede_protection_is_enabled()
    {
        config()->set('query-cache.prevent_stampede', true);

        $post = factory(Post::class)->create();

        $storedPosts = Post::get();
        $tagKey = 'Grafite\QueryCache\Test\Models\Post';
        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "posts"a:0:{}', [$tagKey]);

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $post->id
        );
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_cached_value_is_reused_on_second_call_with_stampede_protection()
    {
        config()->set('query-cache.prevent_stampede', true);

        factory(Post::class)->create();

        $first = Post::get();
        $second = Post::get();

        $this->assertEquals($first->first()->id, $second->first()->id);

        // A brand new post written directly (bypassing the model events) must
        // not appear until the cache is flushed, proving the second read came
        // from the cache rather than the database.
        \DB::table('posts')->insert(['name' => 'uncached', 'created_at' => now(), 'updated_at' => now()]);

        $third = Post::get();
        $this->assertCount($first->count(), $third);
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_count_is_cached_when_stampede_protection_is_enabled()
    {
        config()->set('query-cache.prevent_stampede', true);

        factory(Post::class, 5)->create();

        $this->assertEquals(5, Post::query()->count());

        $tagKey = 'Grafite\QueryCache\Test\Models\Post';
        $cache = $this->getCacheWithTags('qc:sqlitegetselect count(*) as aggregate from "posts"a:0:{}', [$tagKey]);

        $this->assertNotNull($cache);
        $this->assertEquals(5, $cache->first()->aggregate);
    }
}
