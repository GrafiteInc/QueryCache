<?php

namespace Grafite\QueryCache;

use Illuminate\Cache\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

/**
 * Request-scoped state shared by every cacheable query: the resolved config,
 * the cache repository, the in-request memoization store and any flushes
 * deferred until a database transaction commits.
 *
 * Bound as a scoped instance so it is reset between requests/jobs (and per
 * request under Octane), mirroring how Laravel's own MemoizedStore is scoped.
 */
class QueryCacheManager
{
    /**
     * Resolved query-cache config.
     */
    public readonly array $config;

    /**
     * The resolved cache repository.
     *
     * @var Repository|null
     */
    protected $cache = null;

    /**
     * In-request memoized results keyed by cache key.
     *
     * @var array<string, mixed>
     */
    protected array $memo = [];

    /**
     * Tags waiting for a transaction commit, keyed by connection name.
     *
     * @var array<string, array<string, true>>
     */
    protected array $pending = [];

    public function __construct(array $config)
    {
        $this->config = $config + [
            'ttl' => 604800,
            'flush_on_update' => true,
            'cache_driver' => null,
            'cache_prefix' => 'qc',
            'plain_text_keys' => false,
            'prevent_stampede' => false,
            'memoize' => true,
            'memoize_limit' => 1000,
            'skip_in_transactions' => true,
        ];
    }

    /**
     * Get the cache repository for the configured driver.
     *
     * @return Repository
     */
    public function cache()
    {
        return $this->cache ??= app('cache')->driver($this->config['cache_driver']);
    }

    /**
     * Determine whether a query on the given connection should bypass the
     * cache because it runs inside an open transaction.
     */
    public function inTransaction(ConnectionInterface $connection): bool
    {
        return $this->config['skip_in_transactions'] && $connection->transactionLevel() > 0;
    }

    /**
     * Determine whether a result for the key has been memoized.
     */
    public function memoized(string $key): bool
    {
        return $this->config['memoize'] && array_key_exists($key, $this->memo);
    }

    /**
     * Get a memoized result.
     *
     * @return mixed
     */
    public function memoGet(string $key)
    {
        return $this->memo[$key];
    }

    /**
     * Memoize a result, evicting the oldest entry once the limit is reached
     * so long-running processes cannot grow the store without bound.
     *
     * @param  mixed  $value
     */
    public function memoPut(string $key, $value): void
    {
        if (! $this->config['memoize']) {
            return;
        }

        $limit = (int) $this->config['memoize_limit'];

        if ($limit > 0 && count($this->memo) >= $limit && ! array_key_exists($key, $this->memo)) {
            unset($this->memo[array_key_first($this->memo)]);
        }

        $this->memo[$key] = $value;
    }

    /**
     * Forget every memoized result.
     */
    public function memoFlush(): void
    {
        $this->memo = [];
    }

    /**
     * Get the number of memoized results.
     */
    public function memoCount(): int
    {
        return count($this->memo);
    }

    /**
     * Flush every tag in a single cache operation. Stores that do not support
     * tagging cannot flush selectively, so the entire cache is flushed once.
     */
    public function flush(array $tags): bool
    {
        $tags = array_values(array_unique($tags));

        if ($tags) {
            $cache = $this->cache();

            $cache->supportsTags()
                ? $cache->tags($tags)->flush()
                : $cache->flush();
        }

        $this->memoFlush();

        return true;
    }

    /**
     * Defer a flush until the connection's outermost transaction commits, so
     * many writes in one transaction share a single flush and a rollback
     * flushes nothing.
     */
    public function flushAfterCommit(Connection $connection, array $tags): void
    {
        $this->memoFlush();

        $name = $connection->getName();

        foreach ($tags as $tag) {
            $this->pending[$name][$tag] = true;
        }

        // Every write registers a callback so a rolled back savepoint cannot
        // drop the flush owed by the outer transaction; only the first one to
        // run after commit has any work left to do.
        try {
            $connection->afterCommit(fn () => $this->flushPending($name));
        } catch (RuntimeException $e) {
            // No transaction manager to defer to, so flush right away.
            $this->flushPending($name);
        }
    }

    /**
     * Flush the tags deferred for a connection.
     */
    public function flushPending(string $connection): void
    {
        $tags = array_map('strval', array_keys($this->pending[$connection] ?? []));

        unset($this->pending[$connection]);

        if ($tags) {
            $this->flush($tags);
        }
    }
}
