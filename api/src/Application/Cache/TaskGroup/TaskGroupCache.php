<?php

declare(strict_types=1);

namespace App\Application\Cache\TaskGroup;

use App\Application\Port\CacheInterface;
use App\Shared\Utils\CacheKeyUtils;

final readonly class TaskGroupCache
{
    private const DEBOUNCE_TTL_SECONDS = 30;

    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function tryAcquireTaskLock(string $taskId): bool
    {
        return $this->tryAcquireDebounceLock($this->taskKey($taskId));
    }

    /**
     * @param list<string> $taskGroupIds
     */
    public function tryAcquireGroupIdsLock(array $taskGroupIds): bool
    {
        if ($taskGroupIds === []) {
            return false;
        }

        return $this->tryAcquireDebounceLock($this->groupIdsKey($taskGroupIds));
    }

    public function releaseTaskLock(string $taskId): void
    {
        $this->cache->delete($this->taskKey($taskId));
    }

    /**
     * @param list<string> $taskGroupIds
     */
    public function releaseGroupIdsLock(array $taskGroupIds): void
    {
        if ($taskGroupIds === []) {
            return;
        }

        $this->cache->delete($this->groupIdsKey($taskGroupIds));
    }

    private function tryAcquireDebounceLock(string $key): bool
    {
        $acquired = false;

        $this->cache->get(
            $key,
            static function () use (&$acquired): bool {
                $acquired = true;

                return true;
            },
            self::DEBOUNCE_TTL_SECONDS,
        );

        return $acquired;
    }

    private function taskKey(string $taskId): string
    {
        return sprintf('task_group_recalc.task.%s', $taskId);
    }

    /**
     * @param list<string> $taskGroupIds
     */
    private function groupIdsKey(array $taskGroupIds): string
    {
        return sprintf('task_group_recalc.groups.%s', CacheKeyUtils::fingerprintIds($taskGroupIds));
    }
}
