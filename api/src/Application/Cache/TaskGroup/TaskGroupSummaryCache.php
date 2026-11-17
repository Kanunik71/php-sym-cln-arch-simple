<?php

declare(strict_types=1);

namespace App\Application\Cache\TaskGroup;

use App\Application\Model\TaskGroup\Read\TaskGroupSummaryModel;
use App\Application\Port\CacheInterface;

/**
 * Read-through cache for the task group summary.
 *
 * The summary walks every task group with a keyset cursor and aggregates task
 * status counters, so it is the most expensive read in the project. This class
 * owns the key and the TTL; the caller owns the loader.
 *
 * The port is the only dependency: the storage lives in Infrastructure.
 */
final readonly class TaskGroupSummaryCache
{
    public const KEY = 'task_group.summaries';

    public function __construct(
        private CacheInterface $cache,
        private int $ttlSeconds = 60,
    ) {
    }

    /**
     * @param callable(): list<TaskGroupSummaryModel> $loader
     *
     * @return list<TaskGroupSummaryModel>
     */
    public function get(callable $loader): array
    {
        /** @var list<TaskGroupSummaryModel> $summaries */
        $summaries = $this->cache->get(self::KEY, $loader, $this->ttlSeconds);

        return $summaries;
    }

    public function invalidate(): void
    {
        $this->cache->delete(self::KEY);
    }
}
