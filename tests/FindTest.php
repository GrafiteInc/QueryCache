<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Post;
use Illuminate\Support\Facades\Cache;

class FindTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_find()
    {
        $posts = factory(Post::class, 5)->create();
        $firstPost = Post::query()->find(1);
        $tagKey = sha1(cache()->get('tag:Grafite\QueryCache\Test\Models\Post:key'));
        $cache = Cache::get($tagKey.':qc:sqlitegetselect * from "posts" where "posts"."id" = ? limit 1a:1:{i:0;i:1;}');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $firstPost->id
        );

        $this->assertEquals(
            $cache->first()->id,
            $posts->first()->id
        );
    }
}
