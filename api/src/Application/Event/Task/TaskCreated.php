<?php

declare(strict_types=1);

namespace App\Application\Event\Task;

final readonly class TaskCreated
{
    public function __construct(
        public string $taskId,
        public string $name,
        public ?int $estimateTime,
        public ?string $parentId = null,
    ) {
    }
}
