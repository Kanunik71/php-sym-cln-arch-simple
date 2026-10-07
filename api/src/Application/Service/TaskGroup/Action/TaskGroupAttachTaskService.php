<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Action;

use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Mapper\TaskGroup\TaskGroupViewModelMapper;
use App\Application\Model\TaskGroup\Read\TaskGroupViewModel;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;

final readonly class TaskGroupAttachTaskService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private TaskGroupRepositoryInterface $taskGroupRepository,
        private TaskGroupRecalculationDispatcher $recalculationDispatcher,
    ) {
    }

    public function execute(string $userId, string $taskId, string $taskGroupId): TaskGroupViewModel
    {
        $task = $this->taskRepository->findOrFail($taskId);
        $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);

        $taskGroup = $this->taskGroupRepository->findOrFail($taskGroupId);

        if ($this->taskGroupRepository->containsTask($taskGroupId, $taskId)) {
            return TaskGroupViewModelMapper::fromModel($taskGroup);
        }

        $this->taskGroupRepository->attachTask($taskGroupId, $taskId);
        $this->recalculationDispatcher->requestByGroupIds([$taskGroupId]);

        return TaskGroupViewModelMapper::fromModel($taskGroup);
    }
}
