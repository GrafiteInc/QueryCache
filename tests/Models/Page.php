<?php

namespace Grafite\QueryCache\Test\Models;

use Grafite\QueryCache\Traits\QueryCacheable;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use QueryCacheable;

    public $cacheFor = 60;

    protected $fillable = [
        'name',
    ];
}
