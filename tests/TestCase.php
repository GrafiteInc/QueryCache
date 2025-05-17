<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Book;
use Grafite\QueryCache\Test\Models\Kid;
use Grafite\QueryCache\Test\Models\Page;
use Grafite\QueryCache\Test\Models\Post;
use Grafite\QueryCache\Test\Models\User;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        // if ($this->getProvidedData() && method_exists(Model::class, 'preventAccessingMissingAttributes')) {
        //     [$strict] = $this->getProvidedData();
        //     Model::preventAccessingMissingAttributes($strict);
        // }

        $this->resetDatabase();
        $this->clearCache();

        $this->loadLaravelMigrations(['--database' => 'sqlite']);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->withFactories(__DIR__.'/database/factories');

        $this->artisan('migrate', ['--database' => 'sqlite']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app)
    {
        return [
            \Livewire\LivewireServiceProvider::class,
            \Grafite\QueryCache\QueryCacheProvider::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => __DIR__.'/database/database.sqlite',
            'prefix' => '',
        ]);

        $app['config']->set(
            'cache.driver',
            getenv('CACHE_DRIVER') ?: env('CACHE_DRIVER', 'array')
        );

        $app['config']->set(
            'query-cache.plain_text_keys',
            getenv('QUERY_CACHE_PLAIN_TEXT_KEYS') ?: env('QUERY_CACHE_PLAIN_TEXT_KEYS', true)
        );
        $app['config']->set(
            'query-cache.enabled',
            getenv('QUERY_CACHE_ENABLED') ?: env('QUERY_CACHE_ENABLED', true)
        );
        $app['config']->set(
            'query-cache.flush_on_update',
            getenv('QUERY_CACHE_FLUSH_ON_UPDATE') ?: env('QUERY_CACHE_FLUSH_ON_UPDATE', true)
        );

        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('auth.providers.posts.model', Post::class);
        $app['config']->set('auth.providers.kids.model', Kid::class);
        $app['config']->set('auth.providers.books.model', Book::class);
        $app['config']->set('auth.providers.pages.model', Page::class);
        $app['config']->set('app.key', 'wslxrEFGWY6GfGhvN9L3wH3KSRJQQpBD');

        $app['config']->set('view.paths', [
            __DIR__.'/views',
        ]);

        $app['config']->set('livewire.view_path', __DIR__.'/views/livewire');
    }

    /**
     * Reset the database.
     *
     * @return void
     */
    protected function resetDatabase()
    {
        file_put_contents(__DIR__.'/database/database.sqlite', null);
    }

    /**
     * Clear the cache.
     *
     * @return void
     */
    protected function clearCache()
    {
        $this->artisan('cache:clear');
    }

    /**
     * Get the cache with tags, if the driver supports it.
     *
     * @param  array|null  $tags
     * @return mixed
     */
    protected function getCacheWithTags(string $key, $tags = null)
    {
        return $this->driverSupportsTags()
            ? Cache::tags($tags)->get($key)
            : Cache::get($key);
    }

    public static function strictModeContextProvider(): iterable
    {
        yield [true];
        yield [false];
    }

    /**
     * Check if the current driver supports tags.
     */
    protected function driverSupportsTags(): bool
    {
        return ! in_array(config('cache.driver'), ['file', 'database', 'dynamodb']);
    }
}
