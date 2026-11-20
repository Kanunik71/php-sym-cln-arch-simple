<?php

declare(strict_types=1);

namespace App\Application\Port\TaskGroup;

use App\Application\Port\LockHandleInterface;

/**
 * TaskGroup recalculation lock.
 *
 * Channels are independent: a per-task recalculation does not block a
 * per-set-of-groups one. Within a single channel only one recalculation runs at a time.
 */
interface TaskGroupLockInterface
{
    /**
     * Returns null if a recalculation for this task is already running.
     */
    public function acquireByTaskId(string $taskId): ?LockHandleInterface;

    /**
     * Returns null if a recalculation for this set of groups is already running.
     *
     * @param list<string> $taskGroupIds
     */
    public function acquireByGroupIds(array $taskGroupIds): ?LockHandleInterface;
}
