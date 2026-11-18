<?php

declare(strict_types=1);

namespace App\Application\Event\TaskGroup;

/**
 * Пересчёт групп задачи.
 */
final readonly class TaskGroupsRecalculationByTaskRequestedEvent
{
    public function __construct(
        public string $taskId,
    ) {
    }
}
