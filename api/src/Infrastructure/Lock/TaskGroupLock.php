<?php

declare(strict_types=1);

namespace App\Infrastructure\Lock;

use App\Application\Port\LockHandleInterface;
use App\Application\Port\TaskGroup\TaskGroupLockInterface;
use App\Shared\Utils\CacheKeyUtils;
use Symfony\Component\Lock\LockFactory;

/**
 * Лок пересчёта TaskGroup на symfony/lock.
 *
 * Ресурс лока — ключ в общем хранилище: `task_group_recalc.task.{taskId}`
 * либо `task_group_recalc.groups.{sha256(sorted ids)}`.
 */
final readonly class TaskGroupLock implements TaskGroupLockInterface
{
    /**
     * Лок продлевается хендлером перед каждым батчем пересчёта.
     */
    private const LOCK_TTL_SECONDS = 120;

    public function __construct(
        private LockFactory $lockFactory,
    ) {
    }

    public function acquireByTaskId(string $taskId): ?LockHandleInterface
    {
        return $this->acquire(sprintf('task_group_recalc.task.%s', $taskId));
    }

    public function acquireByGroupIds(array $taskGroupIds): ?LockHandleInterface
    {
        if ($taskGroupIds === []) {
            return null;
        }

        return $this->acquire(sprintf(
            'task_group_recalc.groups.%s',
            CacheKeyUtils::fingerprintIds($taskGroupIds),
        ));
    }

    private function acquire(string $resource): ?LockHandleInterface
    {
        $lock = $this->lockFactory->createLock(
            resource: $resource,
            ttl: self::LOCK_TTL_SECONDS,
            autoRelease: false,
        );

        if (!$lock->acquire(blocking: false)) {
            return null;
        }

        return new SymfonyLockHandle($lock);
    }
}
