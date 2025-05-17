<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Page;

class FlushCacheOnUpdateTest extends TestCase
{
    /**
     * @dataProvider strictModeContextProvider
     */
    public function test_flush_cache_on_create()
    {
        factory(Page::class, 5)->create();
        $query = Page::limit(1)->get();
        $tagKey = 'Grafite\QueryCache\Test\Models\Page';

        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "pages" limit 1a:0:{}', [$tagKey]);

        $this->assertNotNull($cache);

        $storedPage = Page::first();

        $this->assertEquals(
            $cache->first()->id,
            $storedPage->id
        );

        // We need to create a new page to flush the cache
        Page::create([
            'name' => '9GAG',
        ]);

        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "pages" limit 1a:0:{}', [$tagKey]);

        $this->assertNull($cache);
    }

    public function test_flush_cache_on_update()
    {
        $page = factory(Page::class)->create();
        $storedPage = Page::first();
        $tagKey = 'Grafite\QueryCache\Test\Models\Page';
        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "pages" limit 1a:0:{}', [$tagKey]);

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $storedPage->id
        );

        $page->update([
            'name' => '9GAG',
        ]);

        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "pages" limit 1a:0:{}', [$tagKey]);

        $this->assertNull($cache);
    }

    public function test_flush_cache_on_delete()
    {
        $page = factory(Page::class)->create();
        $tagKey = 'Grafite\QueryCache\Test\Models\Page';
        $storedPage = Page::first();
        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "pages" limit 1a:0:{}', [$tagKey]);

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $storedPage->id
        );

        $page->delete();

        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "pages" limit 1a:0:{}', [$tagKey]);

        $this->assertNull($cache);
    }

    public function test_flush_cache_on_force_deletion()
    {
        $page = factory(Page::class)->create();
        $tagKey = 'Grafite\QueryCache\Test\Models\Page';
        $storedPage = Page::first();
        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "pages" limit 1a:0:{}', [$tagKey]);

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $storedPage->id
        );

        $page->forceDelete();

        $cache = $this->getCacheWithTags('qc:sqlitegetselect * from "pages" limit 1a:0:{}', [$tagKey]);

        $this->assertNull($cache);
    }
}
