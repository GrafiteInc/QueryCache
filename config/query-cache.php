<?php

/*
|--------------------------------------------------------------------------
| QueryCache Config
|--------------------------------------------------------------------------
*/

return [
    'ttl' => env('QUERY_CACHE_TTL', 604800), // 7 days
    'flush_on_update' => env('QUERY_CACHE_FLUSH_ON_UPDATE', true),
    'cache_driver' => env('QUERY_CACHE_DRIVER', 'redis'),
    'cache_prefix' => env('QUERY_CACHE_PREFIX', 'qc'),
    'plain_text_keys' => env('QUERY_CACHE_PLAIN_TEXT_KEYS', false),
    'prevent_stampede' => env('QUERY_CACHE_PREVENT_STAMPEDE', false),

    // In-request memoization: serve repeated identical queries within a single
    // request from memory instead of hitting the cache backend each time.
    'memoize' => env('QUERY_CACHE_MEMOIZE', true),
];
