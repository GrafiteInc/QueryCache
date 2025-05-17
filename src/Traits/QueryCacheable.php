<?php

namespace Grafite\QueryCache\Traits;

use Grafite\QueryCache\Database\Pivot;
use Grafite\QueryCache\Observers\FlushQueryCacheObserver;
use Grafite\QueryCache\Query\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static bool flushQueryCache(array $tags = [])
 * @method static bool flushQueryCacheWithTag(string $string)
 * @method static \Illuminate\Database\Query\Builder|static cacheFor(\DateTime|int|null $time)
 * @method static \Illuminate\Database\Query\Builder|static cacheForever()
 * @method static \Illuminate\Database\Query\Builder|static dontCache()
 * @method static \Illuminate\Database\Query\Builder|static doNotCache()
 * @method static \Illuminate\Database\Query\Builder|static cacheDriver(string $cacheDriver)
 * @method static \Illuminate\Database\Query\Builder|static cacheBaseTags(array $tags = [])
 */
trait QueryCacheable
{
    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootQueryCacheable()
    {
        if (config('query-cache.flush_on_update')) {
            static::observe(
                static::getFlushQueryCacheObserver()
            );
        }
    }

    public function newPivot(Model $parent, array $attributes, $table, $exists, $using = null)
    {
        return $using ? $using::fromRawAttributes($parent, $attributes, $table, $exists)
            : Pivot::fromAttributes($parent, $attributes, $table, $exists);
    }

    /**
     * Get the observer class name that will
     * observe the changes and will invalidate the cache
     * upon database change.
     *
     * @return string
     */
    protected static function getFlushQueryCacheObserver()
    {
        return FlushQueryCacheObserver::class;
    }

    /**
     * Set the base cache tags that will be present
     * on all queries.
     */
    protected function getCacheBaseTags(): array
    {
        return [
            (string) static::class,
        ];
    }

    /**
     * When invalidating automatically on update, you can specify
     * which tags to invalidate.
     *
     * @param  string|null  $relation
     * @param  \Illuminate\Database\Eloquent\Collection|null  $pivotedModels
     */
    public function getCacheTagsToInvalidateOnUpdate($relation = null, $pivotedModels = null): array
    {
        /** @var \Illuminate\Database\Eloquent\Model $this */
        return $this->getCacheBaseTags();
    }

    /**
     * {@inheritdoc}
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        $builder = new Builder(
            $connection,
            $connection->getQueryGrammar(),
            $connection->getPostProcessor()
        );

        if (property_exists($this, 'cacheFor')) {
            $builder->cacheFor($this->cacheFor);
        }

        return $builder->cacheBaseTags($this->getCacheBaseTags());
    }
}
