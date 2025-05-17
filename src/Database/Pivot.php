<?php

namespace Grafite\QueryCache\Database;

use Grafite\QueryCache\Traits\QueryCacheable;
use Illuminate\Database\Eloquent\Relations\Pivot as PivotRelation;

class Pivot extends PivotRelation
{
    use QueryCacheable;
}
