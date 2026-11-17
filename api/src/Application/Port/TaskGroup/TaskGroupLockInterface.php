<?php

declare(strict_types=1);

namespace App\Application\Port\TaskGroup;

use App\Application\Port\LockHandleInterface;

/**
 * Лок пересчёта TaskGroup.
 *
 * Каналы независимы: пересчёт по задаче не блокирует пересчёт по набору
 * групп. Внутри одного канала пересчёт одновременно не идёт.
 */
interface TaskGroupLockInterface
{
    /**
     * Возвращает null, если пересчёт по этой задаче уже идёт.
     */
    public function acquireByTaskId(string $taskId): ?LockHandleInterface;

    /**
     * Возвращает null, если пересчёт по этому набору групп уже идёт.
     *
     * @param list<string> $taskGroupIds
     */
    public function acquireByGroupIds(array $taskGroupIds): ?LockHandleInterface;
}
