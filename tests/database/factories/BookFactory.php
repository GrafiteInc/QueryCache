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

use Grafite\QueryCache\Test\Models\Book;
use Illuminate\Support\Str;

$factory->define(Book::class, function () {
    return [
        'name' => 'Book'.Str::random(5),
    ];
});
