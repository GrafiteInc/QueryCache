<?php

namespace Grafite\QueryCache\Test\Models;

use Grafite\QueryCache\Traits\QueryCacheable;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use QueryCacheable;

    protected $fillable = [
        'name',
    ];
}
