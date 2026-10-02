<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

use Grafite\QueryCache\Test\Models\Page;
use Illuminate\Support\Str;

$factory->define(Page::class, function () {
    return [
        'name' => 'Page'.Str::random(5),
    ];
});
