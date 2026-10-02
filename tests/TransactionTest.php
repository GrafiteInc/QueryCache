<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Post;
use Illuminate\Support\Facades\DB;

class TransactionTest extends TestCase
{
    private const KEY = 'qc:sqlitegetselect * from "posts"a:0:{}';

    private const TAG = 'Grafite\QueryCache\Test\Models\Post';

    protected function tearDown(): void
    {
        // A failed assertion mid-transaction would otherwise leave the SQLite
        // file locked and hang the next test.
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_reads_inside_a_transaction_are_not_cached()
    {
        factory(Post::class)->create();

        DB::transaction(function () {
            Post::get();
        });

        $this->assertNull($this->getCacheWithTags(self::KEY, [self::TAG]));

        Post::get();

        $this->assertNotNull($this->getCacheWithTags(self::KEY, [self::TAG]));
    }

    public function test_reads_inside_a_transaction_are_cached_when_disabled()
    {
        config()->set('query-cache.skip_in_transactions', false);

        factory(Post::class)->create();

        DB::transaction(function () {
            Post::get();
        });

        $this->assertNotNull($this->getCacheWithTags(self::KEY, [self::TAG]));
    }

    public function test_writes_inside_a_transaction_flush_on_commit()
    {
        factory(Post::class)->create();
        Post::get();

        DB::beginTransaction();

        factory(Post::class)->create();

        $this->assertNotNull($this->getCacheWithTags(self::KEY, [self::TAG]));

        DB::commit();

        $this->assertNull($this->getCacheWithTags(self::KEY, [self::TAG]));
        $this->assertCount(2, Post::get());
    }

    public function test_rolled_back_writes_do_not_flush()
    {
        factory(Post::class)->create();
        Post::get();

        DB::beginTransaction();
        factory(Post::class)->create();
        DB::rollBack();

        $this->assertNotNull($this->getCacheWithTags(self::KEY, [self::TAG]));
        $this->assertCount(1, Post::get());
    }

    public function test_many_writes_in_a_transaction_share_a_single_flush()
    {
        $manager = $this->spyOnQueryCacheManager();
        $manager->shouldReceive('flush')->once()->passthru();

        DB::transaction(function () {
            factory(Post::class, 5)->create();
        });

        $this->assertCount(5, Post::get());
    }

    public function test_rolled_back_savepoint_keeps_the_outer_flush()
    {
        factory(Post::class)->create();
        Post::get();

        DB::beginTransaction();
        factory(Post::class)->create();

        DB::beginTransaction();
        factory(Post::class)->create();
        DB::rollBack();

        DB::commit();

        $this->assertNull($this->getCacheWithTags(self::KEY, [self::TAG]));
        $this->assertCount(2, Post::get());
    }
}
