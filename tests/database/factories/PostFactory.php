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

use Grafite\QueryCache\Test\Models\Post;
use Illuminate\Support\Str;

$factory->define(Post::class, function () {
    return [
        'name' => 'Post'.Str::random(5),
    ];
});
