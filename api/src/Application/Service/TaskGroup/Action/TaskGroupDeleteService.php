<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Action;

use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Policy\TaskGroup\TaskGroupAccessPolicy;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;

final readonly class TaskGroupDeleteService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskGroupAccessPolicy $taskGroupAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private TaskGroupRepositoryInterface $taskGroupRepository,
    ) {
    }

    public function execute(string $userId, string $taskId, string $taskGroupId): void
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);

        $taskGroup = $this->taskGroupRepository->findOrFail($taskGroupId);

        $this->taskGroupAccessPolicy->assertContainsTaskIo($taskGroupId, $taskId);

        $this->taskGroupRepository->delete($taskGroup);
    }
}
