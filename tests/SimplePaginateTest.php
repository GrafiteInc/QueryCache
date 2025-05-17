<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Post;

class SimplePaginateTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_simple_paginate()
    {
        $posts = factory(Post::class, 30)->create();
        $storedPosts = Post::simplePaginate(15);
        $tagKey = 'Grafite\QueryCache\Test\Models\Post';
        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "posts" limit 16 offset 0a:0:{}', [$tagKey]);

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $storedPosts->first()->id
        );

        $this->assertEquals(
            $cache->first()->id,
            $posts->first()->id
        );
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_simple_paginate_with_columns()
    {
        $posts = factory(Post::class, 30)->create();
        $storedPosts = Post::simplePaginate(15, ['name']);

        $tagKey = 'Grafite\QueryCache\Test\Models\Post';
        $cache = $this->getCacheWithTags('qc:sqlitegetselect "name" from "posts" limit 16 offset 0a:0:{}', [$tagKey]);

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->name,
            $storedPosts->first()->name
        );

        $this->assertEquals(
            $cache->first()->name,
            $posts->first()->name
        );
    }
}
