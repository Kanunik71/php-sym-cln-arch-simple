<?php

declare(strict_types=1);

namespace App\Application\Event\TaskGroup;

use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;

final readonly class TaskGroupStatusChangedEvent
{
    public function __construct(
        public string $taskGroupId,
        public TaskGroupStatusEnum $status,
        public ?TaskGroupStatusEnum $previousStatus = null,
    ) {
    }
}
