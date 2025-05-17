<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Post;

class PaginateTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_paginate()
    {
        $posts = factory(Post::class, 30)->create();
        $storedPosts = Post::paginate(15);
        $postsCount = $posts->count();

        $tagKey = 'Grafite\QueryCache\Test\Models\Post';
        $totalCountCache = $this->getCacheWithTags('qc:sqlitegetselect count(*) as aggregate from "posts"a:0:{}', [$tagKey]);
        $postsCache = $this->getCacheWithTags('qc:sqlitegetselect * from "posts" limit 15 offset 0a:0:{}', [$tagKey]);

        $this->assertNotNull($totalCountCache);
        $this->assertNotNull($postsCache);

        $this->assertEquals(
            $totalCountCache->first()->aggregate,
            $postsCount
        );

        $this->assertEquals(15, $postsCache->count());
        $this->assertEquals(1, $postsCache->first()->id);
    }

    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_paginate_with_columns()
    {
        $posts = factory(Post::class, 30)->create();
        $storedPosts = Post::paginate(15, ['name']);
        $postsCount = $posts->count();

        $tagKey = 'Grafite\QueryCache\Test\Models\Post';
        $totalCountCache = $this->getCacheWithTags('qc:sqlitegetselect count(*) as aggregate from "posts"a:0:{}', [$tagKey]);
        $postsCache = $this->getCacheWithTags('qc:sqlitegetselect "name" from "posts" limit 15 offset 0a:0:{}', [$tagKey]);

        $this->assertNotNull($totalCountCache);
        $this->assertNotNull($postsCache);

        $this->assertEquals(
            $totalCountCache->first()->aggregate,
            $postsCount
        );

        $this->assertEquals(15, $postsCache->count());

        $this->assertEquals(
            $posts->first()->name,
            $postsCache->first()->name
        );
    }
}
