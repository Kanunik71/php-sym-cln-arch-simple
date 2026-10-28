<?php

declare(strict_types=1);

namespace App\Application\Policy\TaskGroup;

use App\Application\Exception\TaskGroup\TaskGroupNotFoundException;
use App\Application\Exception\TaskGroup\UnauthorizedTaskGroupAccessException;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;

final readonly class TaskGroupAccessPolicy
{
    public function __construct(
        private TaskGroupRepositoryInterface $taskGroupRepository,
    ) {
    }

    public function assertOwner(string $ownerId, string $userId): void
    {
        if ($ownerId !== $userId) {
            throw UnauthorizedTaskGroupAccessException::create();
        }
    }

    public function assertContainsTaskIo(string $taskGroupId, string $taskId): void
    {
        if (!$this->taskGroupRepository->containsTask($taskGroupId, $taskId)) {
            throw TaskGroupNotFoundException::withId($taskGroupId);
        }
    }
}
