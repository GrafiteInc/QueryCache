<?php

namespace Grafite\QueryCache\Query;

use Closure;
use DateTime;
use Grafite\QueryCache\QueryCacheManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Query\Builder;
use Rennokki\QueryCache\Traits\QueryCacheModule;

trait QueryCaching
{
    /**
     * The number of seconds or the DateTime instance
     * that specifies how long to cache the query.
     *
     * @var int|DateTime
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
     * The request-scoped query cache manager.
     *
     * @var QueryCacheManager|null
     */
    protected $queryCacheManager = null;

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

        // In-request L1 cache: collapse repeated identical queries within a
        // single request into a single backend round-trip.
        $manager = $this->queryCacheManager();

        if ($manager->memoized($key)) {
            return $manager->memoGet($key);
        }

        $cache = $this->getCache();
        $callback = $this->getQueryCacheCallback($method, $columns, $id);
        $time = $this->getCacheFor();

        $value = $this->rememberInCache($cache, $key, $time, $callback);

        $manager->memoPut($key, $value);

        return $value;
    }

    /**
     * Resolve the request-scoped query cache manager.
     */
    protected function queryCacheManager(): QueryCacheManager
    {
        return $this->queryCacheManager ??= app(QueryCacheManager::class);
    }

    /**
     * Store the callback result in the cache, optionally guarding against
     * cache stampedes (the "thundering herd") with an atomic lock.
     *
     * @param  Repository  $cache
     * @param  int|DateTime  $time
     * @return mixed
     */
    protected function rememberInCache($cache, string $key, $time, Closure $callback)
    {
        $forever = ! ($time instanceof DateTime) && $time <= 0;

        if (! $this->queryCacheManager()->config['prevent_stampede']) {
            return $forever
                ? $cache->rememberForever($key, $callback)
                : $cache->remember($key, $time, $callback);
        }

        return $this->rememberWithLock($cache, $key, $time, $forever, $callback);
    }

    /**
     * Resolve a cached value while serializing concurrent misses through an
     * atomic lock so only one worker runs the underlying query.
     *
     * @param  Repository  $cache
     * @param  int|DateTime  $time
     * @return mixed
     */
    protected function rememberWithLock($cache, string $key, $time, bool $forever, Closure $callback)
    {
        $store = $cache->getStore();

        // If the store can't provide locks, fall back to the standard path.
        if (! $store instanceof LockProvider) {
            return $forever
                ? $cache->rememberForever($key, $callback)
                : $cache->remember($key, $time, $callback);
        }

        $value = $cache->get($key);

        if (! is_null($value)) {
            return $value;
        }

        $lock = $store->lock('qc-lock:'.$key, 10);

        if ($lock->get()) {
            try {
                // Another worker may have populated the cache while we waited.
                $value = $cache->get($key);

                if (is_null($value)) {
                    $value = $callback();

                    $forever
                        ? $cache->forever($key, $value)
                        : $cache->put($key, $value, $time);
                }

                return $value;
            } finally {
                $lock->release();
            }
        }

        // Someone else holds the lock; wait for them to populate the cache.
        try {
            $lock->block(5);
            $lock->release();
        } catch (LockTimeoutException $e) {
            // Fall through and compute directly rather than blocking further.
        }

        $value = $cache->get($key);

        return is_null($value) ? $callback() : $value;
    }

    /**
     * Get the query cache callback.
     *
     * @param  array|string  $columns
     * @return Closure
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

        return hash('xxh128', $key);
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
        if (! $tags) {
            $tags = $this->getCacheBaseTags() ?: [];
        }

        return $this->queryCacheManager()->flush($tags);
    }

    /**
     * Flush the cache for a specific tag. Stores that do not support tagging
     * cannot flush selectively, so the entire cache is flushed instead.
     *
     * @param  Repository|null  $cache  Unused; kept for backwards compatibility.
     */
    public function flushQueryCacheWithTag(string $tag, $cache = null): bool
    {
        return $this->queryCacheManager()->flush([$tag]);
    }

    /**
     * Indicate that the query results should be cached.
     *
     * @param  DateTime|int|null  $time
     * @return QueryCacheModule
     */
    public function cacheFor($time)
    {
        $this->cacheFor = $time;

        return $this;
    }

    /**
     * Indicate that the query results should be cached forever.
     *
     * @return Builder|static
     */
    public function cacheForever()
    {
        return $this->cacheFor(-1);
    }

    /**
     * Indicate that the query should not be cached.
     *
     * @return Builder|static
     */
    public function dontCache(bool $avoidCache = true)
    {
        $this->avoidCache = $avoidCache;

        return $this;
    }

    /**
     * Alias for dontCache().
     *
     * @return Builder|static
     */
    public function doNotCache(bool $avoidCache = true)
    {
        return $this->dontCache($avoidCache);
    }

    /**
     * Get the cache driver.
     *
     * @return CacheManager
     */
    public function getCacheDriver()
    {
        return $this->queryCacheManager()->cache();
    }

    /**
     * Get the cache object with tags assigned, if applicable.
     *
     * @return CacheManager
     */
    public function getCache()
    {
        $cache = $this->getCacheDriver();

        $tags = $this->getCacheBaseTags() ?: [];

        return $tags && $cache->supportsTags() ? $cache->tags($tags) : $cache;
    }

    /**
     * Check if the cache operation should be avoided.
     */
    public function shouldAvoidCache(): bool
    {
        return $this->avoidCache
            || $this->queryCacheManager()->inTransaction($this->connection);
    }

    /**
     * Check if the cache operation key should use a plain
     * query key.
     */
    public function shouldUsePlainKey(): bool
    {
        return (bool) $this->queryCacheManager()->config['plain_text_keys'];
    }

    /**
     * Get the cache time attribute.
     *
     * @return int|DateTime
     */
    public function getCacheFor()
    {
        return $this->cacheFor ?? $this->queryCacheManager()->config['ttl'];
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
        return $this->queryCacheManager()->config['cache_prefix'];
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
