<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Role;
use Grafite\QueryCache\Test\Models\User;
use Illuminate\Support\Facades\DB;

class FlushCacheOnUpdatePivotTest extends TestCase
{
    public function test_belongs_to_many()
    {
        $key = 'qc:sqlitegetselect "roles".*, "role_user"."user_id" as "pivot_user_id", "role_user"."role_id" as "pivot_role_id" from "roles" inner join "role_user" on "roles"."id" = "role_user"."role_id" where "role_user"."user_id" = ?a:1:{i:0;i:1;}';

        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();

        $storedRoles = $user->roles()->get();

        $cache = $this->getCacheWithTags($key, [
            'Grafite\QueryCache\Test\Models\Role',
        ]);

        $this->assertNull($cache->first());
        $this->assertEquals(0, $storedRoles->count());
        $this->assertEquals(0, DB::table('role_user')->count());

        // This should flush the cache, but it doesn't because of some Laravel stuff I guess?
        $user->roles()->attach($role->id);

        // We can see there is an attached role
        $this->assertEquals(1, DB::table('role_user')->count());

        // This shouldnt be needed because the attach should flush the cache
        // Role::flushQueryCache();

        $storedRoles = $user->roles()->get();

        $this->assertEquals(
            $role->id,
            $storedRoles->first()->id
        );

        $user->roles()->detach($role->id);

        // There is no easy way around this, so we have to do it manually
        // Role::flushQueryCache();

        $storedRoles = $user->roles()->get();

        $this->assertEquals(0, $storedRoles->count());
    }
}
