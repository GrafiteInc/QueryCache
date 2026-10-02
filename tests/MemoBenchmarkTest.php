<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Observers\FlushQueryCacheObserver;
use Grafite\QueryCache\Test\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Not part of the default suite. Run explicitly with:
 *
 *   XDEBUG_MODE=off ./vendor/bin/phpunit --group benchmark
 *
 * If a Redis server is reachable on 127.0.0.1:6379 it is benchmarked too,
 * which is where memoization and flush batching actually pay off (they
 * remove round-trips).
 *
 * @group benchmark
 */
class MemoBenchmarkTest extends TestCase
{
    /**
     * Number of repeated reads timed per measurement.
     */
    private const READS = 20000;

    /**
     * Number of model invalidations timed per measurement.
     */
    private const WRITES = 500;

    /**
     * Number of multi-tag flushes timed per measurement.
     */
    private const FLUSHES = 500;

    /**
     * Small result set so Eloquent hydration (paid equally by both paths)
     * does not drown out the cache-access cost we are trying to compare.
     */
    private const ROWS = 3;

    private const DRIVERS = ['array', 'file', 'redis'];

    public function test_memoization_repeat_read_cost()
    {
        factory(Post::class, self::ROWS)->create();

        foreach ($this->availableDrivers() as $driver) {
            $this->header(sprintf('Cache driver: %s   |   %s repeated Post::get() reads (%d rows)',
                $driver, number_format(self::READS), self::ROWS));

            $l2 = $this->measureReads($driver, false);
            $l1 = $this->measureReads($driver, true);

            $this->report('L2 (backend hit)', $l2, $l2);
            $this->report('L1 (memoized)', $l1, $l2);
            $this->footer();
        }

        $this->assertTrue(true);
    }

    public function test_invalidation_cost()
    {
        $post = factory(Post::class)->create();
        $observer = new FlushQueryCacheObserver;

        foreach ($this->availableDrivers() as $driver) {
            $this->header(sprintf('Cache driver: %s   |   %s model invalidations (observer updated())',
                $driver, number_format(self::WRITES)));

            $outside = $this->measure($driver, self::WRITES, function () use ($observer, $post) {
                $observer->updated($post);
            });

            $inside = $this->measure($driver, self::WRITES, function () use ($observer, $post) {
                $observer->updated($post);
            }, function (callable $run) {
                DB::transaction($run);
            });

            $this->report('outside transaction', $outside, $outside);
            $this->report('inside transaction', $inside, $outside);
            $this->footer();
        }

        $this->assertTrue(true);
    }

    public function test_multi_tag_flush_cost()
    {
        foreach ($this->availableDrivers() as $driver) {
            $this->header(sprintf('Cache driver: %s   |   %s flushQueryCache() calls with 3 tags',
                $driver, number_format(self::FLUSHES)));

            $result = $this->measure($driver, self::FLUSHES, function () {
                Post::flushQueryCache(['bench-a', 'bench-b', 'bench-c']);
            });

            $this->report('3-tag flush', $result, $result);
            $this->footer();
        }

        $this->assertTrue(true);
    }

    /**
     * Warm the caches, then time READS repeated identical reads.
     *
     * @return array{total: float, perCall: float}
     */
    private function measureReads(string $driver, bool $memoize): array
    {
        config()->set('query-cache.memoize', $memoize);

        return $this->measure($driver, self::READS, fn () => Post::get(), null, fn () => Post::get());
    }

    /**
     * Time $iterations calls of $operation against a cold cache, optionally
     * wrapping the whole loop (e.g. in a transaction) and warming first.
     *
     * @return array{total: float, perCall: float}
     */
    private function measure(string $driver, int $iterations, callable $operation, ?callable $wrap = null, ?callable $warm = null): array
    {
        config()->set('query-cache.cache_driver', $driver);

        Cache::store($driver)->flush();
        app()->forgetScopedInstances();

        if ($warm) {
            $warm();
        }

        $loop = function () use ($iterations, $operation) {
            for ($i = 0; $i < $iterations; $i++) {
                $operation();
            }
        };

        $start = hrtime(true);

        $wrap ? $wrap($loop) : $loop();

        $totalNs = hrtime(true) - $start;

        return [
            'total' => $totalNs / 1_000_000,             // ms
            'perCall' => $totalNs / $iterations / 1_000, // µs
        ];
    }

    /**
     * @return array<int, string>
     */
    private function availableDrivers(): array
    {
        $drivers = array_values(array_filter(self::DRIVERS, function ($driver) {
            if ($this->driverIsAvailable($driver)) {
                return true;
            }

            $this->write("\nSkipping '{$driver}' driver: not reachable.");

            return false;
        }));

        return $drivers;
    }

    private function driverIsAvailable(string $driver): bool
    {
        if (in_array($driver, ['array', 'file'])) {
            return true;
        }

        if ($driver === 'redis') {
            config()->set('database.redis.client', 'phpredis');
            config()->set('database.redis.default', [
                'host' => '127.0.0.1',
                'port' => 6379,
                'database' => 0,
            ]);
            config()->set('cache.stores.redis', [
                'driver' => 'redis',
                'connection' => 'default',
            ]);

            try {
                $token = 'qc-'.uniqid();
                Cache::store('redis')->put('qc_bench_ping', $token, 5);
                $got = Cache::store('redis')->get('qc_bench_ping');

                if ($got !== $token) {
                    $this->write('redis probe mismatch: got '.var_export($got, true));
                }

                return $got === $token;
            } catch (Throwable $e) {
                $this->write('redis err: '.get_class($e).': '.$e->getMessage());

                return false;
            }
        }

        return false;
    }

    private function header(string $title): void
    {
        $line = str_repeat('=', 78);

        $this->write("\n{$line}");
        $this->write($title);
        $this->write($line);
        $this->write(sprintf('%-24s %14s %16s %10s', 'mode', 'total (ms)', 'per call (µs)', 'vs base'));
        $this->write(str_repeat('-', 78));
    }

    private function footer(): void
    {
        $this->write(str_repeat('=', 78));
    }

    /**
     * @param  array{total: float, perCall: float}  $result
     * @param  array{total: float, perCall: float}  $baseline
     */
    private function report(string $label, array $result, array $baseline): void
    {
        $speedup = $result['total'] > 0
            ? sprintf('%.2fx', $baseline['total'] / $result['total'])
            : '—';

        $this->write(sprintf('%-24s %14.2f %16.2f %10s',
            $label, $result['total'], $result['perCall'], $speedup));
    }

    private function write(string $message): void
    {
        fwrite(STDERR, $message."\n");
    }
}
