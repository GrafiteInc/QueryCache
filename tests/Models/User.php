<?php

namespace Grafite\QueryCache\Test\Models;

use Grafite\QueryCache\Traits\QueryCacheable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use QueryCacheable;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
