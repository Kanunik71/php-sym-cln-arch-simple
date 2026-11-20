<?php

declare(strict_types=1);

namespace App\Application\Event\TaskGroup;

final readonly class TaskGroupRecalculationByTaskRequestedEvent
{
    public function __construct(
        public string $taskId,
    ) {
    }
}
