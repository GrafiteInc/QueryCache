<?php

namespace Grafite\QueryCache\Query;

use DateTime;
use BadMethodCallException;

trait QueryCaching
{
    /**
     * The number of seconds or the DateTime instance
     * that specifies how long to cache the query.
     *
     * @var int|\DateTime
     */
    protected $cacheFor;

    /**
     * The tags for the query cache that
     * will be present on all queries.
     *
     * @var null|array
     */
    protected $cacheBaseTags = null;

    /**
     * Set if the caching should be avoided.
     *
     * @var bool
     */
    protected $avoidCache = false;

    /**
     * Get the cache from the current query.
     *
     * @return array
     */
    public function getFromQueryCache(string $method = 'get', array $columns = ['*'], ?string $id = null)
    {
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        $key = $this->getCacheKey($method);
        $cache = $this->getCache();
        $callback = $this->getQueryCacheCallback($method, $columns, $id);
        $time = $this->getCacheFor();

        // If the cache is in use, check the in memory cache first
        if (method_exists(cache(), 'memo') && cache()->memo('file')->has($key)) {
            return cache()->memo('file')->get($key);
        }

        if ($time instanceof DateTime || $time > 0) {
            $value = $cache->remember($key, $time, $callback);
        } else {
            $value = $cache->rememberForever($key, $callback);
        }

        if (method_exists(cache(), 'memo')) {
            cache()->memo('file')->put($key, $value);
        }

        return $value;
    }

    /**
     * Get the query cache callback.
     *
     * @param  array|string  $columns
     * @return \Closure
     */
    public function getQueryCacheCallback(string $method = 'get', $columns = ['*'], ?string $id = null)
    {
        return function () use ($method, $columns, $id) {
            // Avoid cache for a first query
            $this->avoidCache = true;

            if ($method === 'find') {
                return $this->{$method}($id, $columns);
            }

            return $this->{$method}($columns);
        };
    }

    /**
     * Get a unique cache key for the complete query.
     */
    public function getCacheKey(string $method = 'get', ?string $id = null, ?string $appends = null): string
    {
        $key = $this->generateCacheKey($method, $id, $appends);
        $prefix = $this->getCachePrefix();

        return "{$prefix}:{$key}";
    }

    /**
     * Generate the unique cache key for the query.
     */
    public function generateCacheKey(string $method = 'get', ?string $id = null, ?string $appends = null): string
    {
        $key = $this->generatePlainCacheKey($method, $id, $appends);

        if ($this->shouldUsePlainKey()) {
            return $key;
        }

        return md5($key);
    }

    /**
     * Generate the plain unique cache key for the query.
     */
    public function generatePlainCacheKey(string $method = 'get', ?string $id = null, ?string $appends = null): string
    {
        $name = $this->connection->getName();

        // Count has no Sql, that's why it can't be used ->toSql()
        if ($method === 'count') {
            return $name.$method.$id.serialize($this->getBindings()).$appends;
        }

        return $name.$method.$id.$this->toSql().serialize($this->getBindings()).$appends;
    }

    /**
     * Flush the cache that contains specific tags.
     */
    public function flushQueryCache(array $tags = []): bool
    {
        $cache = $this->getCacheDriver();

        if (! method_exists($cache, 'tags')) {
            return false;
        }

        if (! $tags) {
            $tags = $this->getCacheBaseTags();
        }

        if (method_exists(cache(), 'memo')) {
            cache()->memo('file')->flush();
        }

        foreach ($tags as $tag) {
            $this->flushQueryCacheWithTag($tag);
        }

        return true;
    }

    /**
     * Flush the cache for a specific tag.
     */
    public function flushQueryCacheWithTag(string $tag): bool
    {
        $cache = $this->getCacheDriver();

        try {
            return $cache->tags($tag)->flush();
        } catch (BadMethodCallException $e) {
            return $cache->flush();
        }
    }

    /**
     * Indicate that the query results should be cached.
     *
     * @param  \DateTime|int|null  $time
     * @return \Rennokki\QueryCache\Traits\QueryCacheModule
     */
    public function cacheFor($time)
    {
        $this->cacheFor = $time;

        return $this;
    }

    /**
     * Indicate that the query results should be cached forever.
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function cacheForever()
    {
        return $this->cacheFor(-1);
    }

    /**
     * Indicate that the query should not be cached.
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function dontCache(bool $avoidCache = true)
    {
        $this->avoidCache = $avoidCache;

        return $this;
    }

    /**
     * Alias for dontCache().
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function doNotCache(bool $avoidCache = true)
    {
        return $this->dontCache($avoidCache);
    }

    /**
     * Get the cache driver.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    public function getCacheDriver()
    {
        return app('cache')->driver(config('query-cache.cache_driver'));
    }

    /**
     * Get the cache object with tags assigned, if applicable.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    public function getCache()
    {
        $cache = $this->getCacheDriver();

        $tags = array_merge(
            $this->getCacheBaseTags() ?: []
        );

        try {
            return $tags ? $cache->tags($tags) : $cache;
        } catch (BadMethodCallException $e) {
            return $cache;
        }
    }

    /**
     * Check if the cache operation should be avoided.
     */
    public function shouldAvoidCache(): bool
    {
        return $this->avoidCache;
    }

    /**
     * Check if the cache operation key should use a plain
     * query key.
     */
    public function shouldUsePlainKey(): bool
    {
        return config('query-cache.plain_text_keys', false);
    }

    /**
     * Get the cache time attribute.
     *
     * @return int|\DateTime
     */
    public function getCacheFor()
    {
        return $this->cacheFor ?? config('query-cache.ttl', 604800);
    }

    public function cacheBaseTags($tags)
    {
        $this->cacheBaseTags = $tags;

        return $this;
    }

    /**
     * Get the base cache tags attribute.
     *
     * @return array|null
     */
    public function getCacheBaseTags()
    {
        return $this->cacheBaseTags;
    }

    /**
     * Get the cache prefix attribute.
     */
    public function getCachePrefix(): string
    {
        return config('query-cache.prefix', 'qc');
    }

    public function appendCacheTags($tags)
    {
        if (is_string($tags)) {
            $tags = [$tags];
        }

        if (is_array($this->cacheBaseTags)) {
            $this->cacheBaseTags = array_merge($this->cacheBaseTags, $tags);
        } else {
            $this->cacheBaseTags = $tags;
        }

        return $this;
    }

    /**
     * Get the cache tags for the query.
     *
     * @return array
     */
    public function getCacheTags()
    {
        return $this->cacheBaseTags ?: [];
    }
}
