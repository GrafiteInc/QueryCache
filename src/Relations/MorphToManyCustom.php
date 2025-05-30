<?php

namespace Grafite\QueryCache\Relations;

use Grafite\QueryCache\Traits\FiresPivotEventsTrait;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class MorphToManyCustom extends MorphToMany
{
    use FiresPivotEventsTrait;
}
