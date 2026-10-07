<?php

declare(strict_types=1);

namespace App\Application\Policy\TaskGroup;

use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Application\Exception\TaskGroup\TaskGroupNotFoundException;

final readonly class TaskGroupAccessPolicy
{
    public function __construct(
        private TaskGroupRepositoryInterface $taskGroupRepository,
    ) {
    }

    public function assertContainsTaskIo(string $taskGroupId, string $taskId): void
    {
        if (!$this->taskGroupRepository->containsTask($taskGroupId, $taskId)) {
            throw TaskGroupNotFoundException::withId($taskGroupId);
        }
    }
}
