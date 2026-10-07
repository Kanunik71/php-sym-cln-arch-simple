<?php

declare(strict_types=1);

namespace App\Application\Event\TaskGroup;

final readonly class TaskGroupsRecalculationRequestedEvent
{
    /**
     * @param list<string> $taskGroupIds
     */
    public function __construct(
        public ?string $taskId = null,
        public array $taskGroupIds = [],
    ) {
    }
}
