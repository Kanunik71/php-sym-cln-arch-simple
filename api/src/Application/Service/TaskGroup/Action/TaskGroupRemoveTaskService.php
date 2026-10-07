<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Action;

use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Mapper\TaskGroup\TaskGroupViewModelMapper;
use App\Application\Model\TaskGroup\Read\TaskGroupViewModel;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Policy\TaskGroup\TaskGroupAccessPolicy;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;

final readonly class TaskGroupRemoveTaskService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskGroupAccessPolicy $taskGroupAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private TaskGroupRepositoryInterface $taskGroupRepository,
        private TaskGroupRecalculationDispatcher $recalculationDispatcher,
    ) {
    }

    public function execute(
        string $userId,
        string $taskId,
        string $taskGroupId,
        string $removeTaskId,
    ): TaskGroupViewModel {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);

        $taskGroup = $this->taskGroupRepository->findOrFail($taskGroupId);

        $this->taskGroupAccessPolicy->assertContainsTaskIo($taskGroupId, $taskId);

        if (!$this->taskGroupRepository->containsTask($taskGroupId, $removeTaskId)) {
            return TaskGroupViewModelMapper::fromModel($taskGroup);
        }

        $this->taskGroupRepository->detachTask($taskGroupId, $removeTaskId);

        $this->recalculationDispatcher->requestByGroupIds([$taskGroupId]);

        return TaskGroupViewModelMapper::fromModel($taskGroup);
    }
}
