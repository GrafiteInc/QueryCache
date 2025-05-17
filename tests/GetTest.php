<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Post;

class GetTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_get()
    {
        $post = factory(Post::class)->create();
        $storedPosts = Post::get();
        $tagKey = 'Grafite\QueryCache\Test\Models\Post';
        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "posts"a:0:{}', [$tagKey]);

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $storedPosts->first()->id
        );

        $this->assertEquals(
            $cache->first()->id,
            $post->id
        );
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_get_with_columns()
    {
        $post = factory(Post::class)->create();
        $storedPosts = Post::get(['name']);
        $tagKey = 'Grafite\QueryCache\Test\Models\Post';
        $cache = $this->getCacheWithTags('qc:sqlitegetselect "name" from "posts"a:0:{}', [$tagKey]);

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->name,
            $storedPosts->first()->name
        );

        $this->assertEquals(
            $cache->first()->name,
            $post->name
        );
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_get_with_string_columns()
    {
        $post = factory(Post::class)->create();
        $storedPosts = Post::get('name');
        $tagKey = 'Grafite\QueryCache\Test\Models\Post';
        $cache = $this->getCacheWithTags('qc:sqlitegetselect "name" from "posts"a:0:{}', [$tagKey]);

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->name,
            $storedPosts->first()->name
        );

        $this->assertEquals(
            $cache->first()->name,
            $post->name
        );
    }
}
