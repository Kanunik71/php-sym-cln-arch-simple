<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Query;

use App\Application\Mapper\TaskGroup\TaskGroupViewModelMapper;
use App\Application\Model\TaskGroup\Read\TaskGroupViewModel;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Policy\TaskGroup\TaskGroupAccessPolicy;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;

final readonly class TaskGroupQueryService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskGroupAccessPolicy $taskGroupAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private TaskGroupRepositoryInterface $taskGroupRepository,
    ) {
    }

    public function execute(string $userId, string $taskId, string $taskGroupId): TaskGroupViewModel
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);

        $taskGroup = $this->taskGroupRepository->findOrFail($taskGroupId);

        $this->taskGroupAccessPolicy->assertContainsTaskIo($taskGroupId, $taskId);

        return TaskGroupViewModelMapper::fromModel($taskGroup);
    }
}
