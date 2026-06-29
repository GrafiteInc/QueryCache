<?php

namespace Grafite\QueryCache\Test;

use Grafite\QueryCache\Test\Models\Post;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Not part of the default suite. Run explicitly with:
 *
 *   ./vendor/bin/phpunit --group benchmark
 *
 * If a Redis server is reachable on 127.0.0.1:6379 it is benchmarked too,
 * which is where memoization actually pays off (it removes round-trips).
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
     * Small result set so Eloquent hydration (paid equally by both paths)
     * does not drown out the cache-access cost we are trying to compare.
     */
    private const ROWS = 3;

    public function test_memoization_repeat_read_cost()
    {
        factory(Post::class, self::ROWS)->create();

        $line = str_repeat('=', 70);

        foreach (['array', 'redis'] as $driver) {
            if (! $this->driverIsAvailable($driver)) {
                $this->write("\nSkipping '{$driver}' driver: not reachable.");

                continue;
            }

            $this->write("\n{$line}");
            $this->write(sprintf('Cache driver: %s   |   %s repeated Post::get() reads (%d rows)',
                $driver, number_format(self::READS), self::ROWS));
            $this->write($line);
            $this->write(sprintf('%-20s %14s %16s %10s', 'mode', 'total (ms)', 'per call (µs)', 'vs L2'));
            $this->write(str_repeat('-', 70));

            $l2 = $this->measure($driver, false);
            $l1 = $this->measure($driver, true);

            $this->report('L2 (backend hit)', $l2, $l2);
            $this->report('L1 (memoized)', $l1, $l2);

            $perCallSaved = $l2['perCall'] - $l1['perCall'];
            $this->write(str_repeat('-', 70));
            $this->write(sprintf('Per-call saved by memoization: %.2f µs   (%s backend reads removed)',
                $perCallSaved, number_format(self::READS - 1)));
            $this->write(sprintf('Extrapolated saving across this run: %.1f ms',
                $perCallSaved * (self::READS - 1) / 1000));
            $this->write($line);
        }

        $this->write('');
        $this->assertTrue(true);
    }

    /**
     * Warm the caches, then time READS repeated identical reads.
     *
     * @return array{total: float, perCall: float}
     */
    private function measure(string $driver, bool $memoize): array
    {
        config()->set('query-cache.cache_driver', $driver);
        config()->set('query-cache.memoize', $memoize);

        // Start cold so the first read is the single shared miss, then warm
        // both L2 (backend) and, when enabled, L1 (in-request memo).
        Cache::store($driver)->flush();
        app()->forgetScopedInstances();
        Post::get();

        $start = hrtime(true);

        for ($i = 0; $i < self::READS; $i++) {
            Post::get();
        }

        $totalNs = hrtime(true) - $start;

        return [
            'total' => $totalNs / 1_000_000,             // ms
            'perCall' => $totalNs / self::READS / 1_000, // µs
        ];
    }

    private function driverIsAvailable(string $driver): bool
    {
        if ($driver === 'array') {
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

    /**
     * @param  array{total: float, perCall: float}  $result
     * @param  array{total: float, perCall: float}  $baseline
     */
    private function report(string $label, array $result, array $baseline): void
    {
        $speedup = $result['total'] > 0
            ? sprintf('%.2fx', $baseline['total'] / $result['total'])
            : '—';

        $this->write(sprintf('%-20s %14.2f %16.2f %10s',
            $label, $result['total'], $result['perCall'], $speedup));
    }

    private function write(string $message): void
    {
        fwrite(STDERR, $message."\n");
    }
}
