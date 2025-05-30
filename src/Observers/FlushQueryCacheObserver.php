<?php

namespace Grafite\QueryCache\Observers;

use Exception;
use Illuminate\Database\Eloquent\Model;

class FlushQueryCacheObserver
{
    /**
     * Handle the Model "created" event.
     *
     * @return void
     */
    public function created(Model $model)
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "updated" event.
     *
     * @return void
     */
    public function updated(Model $model)
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "deleted" event.
     *
     * @return void
     */
    public function deleted(Model $model)
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "forceDeleted" event.
     *
     * @return void
     */
    public function forceDeleted(Model $model)
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "restored" event.
     *
     * @return void
     */
    public function restored(Model $model)
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the Model "pivotAttached" event.
     *
     * @return void
     */
    public function pivotAttached(Model $model, $relation, $pivotedModels)
    {
        $this->invalidateCache($model, $relation, $pivotedModels);
    }

    /**
     * Handle the Model "pivotDetached" event.
     *
     * @return void
     */
    public function pivotDetached(Model $model, $relation, $pivotedModels)
    {
        $this->invalidateCache($model, $relation, $pivotedModels);
    }

    /**
     * Handle the Model "pivotUpdated" event.
     *
     * @return void
     */
    public function pivotUpdated(Model $model, $relation, $pivotedModels)
    {
        $this->invalidateCache($model, $relation, $pivotedModels);
    }
    /**
     * Handle the Model "pivotUpdating" event.
     *
     * @return void
     */
    public function pivotUpdating(Model $model, $relation, $pivotedModels)
    {
        $this->invalidateCache($model, $relation, $pivotedModels);
    }

    /**
     * Invalidate the cache for a model.
     *
     * @param  string|null  $relation
     * @param  \Illuminate\Database\Eloquent\Collection|null  $pivotedModels
     *
     * @throws Exception
     */
    protected function invalidateCache(Model $model, $relation = null, $pivotedModels = null): void
    {
        $class = get_class($model);

        $tags = $model->getCacheTagsToInvalidateOnUpdate($relation);

        if (! $tags) {
            throw new Exception('Automatic invalidation for '.$class.' works only if at least one tag to be invalidated is specified.');
        }

        $class::flushQueryCache($tags);
    }
}
