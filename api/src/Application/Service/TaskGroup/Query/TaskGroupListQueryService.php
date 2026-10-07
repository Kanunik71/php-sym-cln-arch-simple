<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Query;

use App\Application\Mapper\TaskGroup\TaskGroupViewModelMapper;
use App\Application\Model\TaskGroup\Read\TaskGroupViewModel;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;

final readonly class TaskGroupListQueryService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private TaskGroupRepositoryInterface $taskGroupRepository,
    ) {
    }

    /** @return list<TaskGroupViewModel> */
    public function execute(string $userId, string $taskId): array
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);

        $taskGroups = $this->taskGroupRepository->listByTaskId($taskId);

        return TaskGroupViewModelMapper::fromModelList($taskGroups);
    }
}
