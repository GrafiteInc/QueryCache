<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\QueryCacheManager;
use Grafite\QueryCache\Test\Models\Post;
use Grafite\QueryCache\Test\Models\Role;
use Grafite\QueryCache\Test\Models\User;
use Illuminate\Cache\Repository;
use Illuminate\Cache\TaggedCache;
use Mockery;

class FlushBatchingTest extends TestCase
{
    public function test_multiple_tags_are_flushed_in_one_call()
    {
        $tagged = Mockery::mock(TaggedCache::class);
        $tagged->shouldReceive('flush')->once()->andReturn(true);

        $cache = Mockery::mock(Repository::class);
        $cache->shouldReceive('supportsTags')->andReturn(true);
        $cache->shouldReceive('tags')->once()->with(['a', 'b', 'c'])->andReturn($tagged);

        $this->managerWithCache($cache)->flush(['a', 'b', 'a', 'c']);
    }

    public function test_store_without_tags_is_flushed_once()
    {
        $cache = Mockery::mock(Repository::class);
        $cache->shouldReceive('supportsTags')->andReturn(false);
        $cache->shouldReceive('flush')->once()->andReturn(true);

        $this->managerWithCache($cache)->flush(['a', 'b', 'c']);
    }

    public function test_flushing_extra_tags_still_flushes_the_model_tag()
    {
        factory(Post::class)->create();
        Post::get();

        $key = 'qc:sqlitegetselect * from "posts"a:0:{}';
        $tag = 'Grafite\QueryCache\Test\Models\Post';

        $this->assertNotNull($this->getCacheWithTags($key, [$tag]));

        Post::flushQueryCache(['unrelated', $tag]);

        $this->assertNull($this->getCacheWithTags($key, [$tag]));
    }

    public function test_updating_a_pivot_flushes_once()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();

        $user->roles()->attach($role->id);

        $manager = $this->spyOnQueryCacheManager();
        $manager->shouldReceive('flush')->once()->passthru();

        $user->roles()->updateExistingPivot($role->id, ['role_id' => $role->id]);
    }

    private function managerWithCache($cache): QueryCacheManager
    {
        $manager = Mockery::mock(QueryCacheManager::class, [[]])->makePartial();
        $manager->shouldReceive('cache')->andReturn($cache);

        return $manager;
    }
}
