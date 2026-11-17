<?php

declare(strict_types=1);

namespace App\Application\Event\TaskGroup;

/**
 * Пересчёт указанных групп.
 */
final readonly class TaskGroupsRecalculationByGroupIdsRequestedEvent
{
    /**
     * @param list<string> $taskGroupIds
     */
    public function __construct(
        public array $taskGroupIds,
    ) {
    }
}
