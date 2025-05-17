<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Post;

class FirstTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_first()
    {
        $post = factory(Post::class)->create();
        $storedPost = Post::first();
        $tagKey = 'Grafite\QueryCache\Test\Models\Post';
        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "posts" limit 1a:0:{}', [$tagKey]);

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $storedPost->id
        );
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_first_with_columns()
    {
        $post = factory(Post::class)->create();
        $storedPost = Post::first(['name']);
        $tagKey = 'Grafite\QueryCache\Test\Models\Post';
        $cache = $this->getCacheWithTags('qc:sqlitegetselect "name" from "posts" limit 1a:0:{}', [$tagKey]);

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->name,
            $storedPost->name
        );
    }
}
