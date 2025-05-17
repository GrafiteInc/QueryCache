<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Post;
use Illuminate\Support\Facades\Cache;

class CountTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_count()
    {
        factory(Post::class, 5)->create();
        $postsCount = Post::query()->count();
        $tagKey = sha1(cache()->get('tag:Grafite\QueryCache\Test\Models\Post:key'));
        $cache = cache()->get($tagKey.':qc:sqlitegetselect count(*) as aggregate from "posts"a:0:{}');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->aggregate,
            $postsCount
        );
    }

    public function test_count_with_columns()
    {
        factory(Post::class, 5)->create();
        $postsCount = Post::query()->count('name');
        $tagKey = sha1(cache()->get('tag:Grafite\QueryCache\Test\Models\Post:key'));
        $cache = Cache::get($tagKey.':qc:sqlitegetselect count("name") as aggregate from "posts"a:0:{}');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->aggregate,
            $postsCount
        );
    }
}
