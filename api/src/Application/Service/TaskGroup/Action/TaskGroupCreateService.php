<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Action;

use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Mapper\TaskGroup\TaskGroupViewModelMapper;
use App\Application\Model\TaskGroup\Action\TaskGroupCreateModel;
use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Application\Model\TaskGroup\Read\TaskGroupViewModel;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Application\Service\TaskGroup\Lifecycle\TaskGroupLifecycleService;

final readonly class TaskGroupCreateService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private TaskGroupRepositoryInterface $taskGroupRepository,
        private TaskGroupRecalculationDispatcher $recalculationDispatcher,
        private TaskGroupLifecycleService $taskGroupLifecycleService,
    ) {
    }

    public function execute(TaskGroupCreateModel $model, string $userId): TaskGroupViewModel
    {
        foreach ($model->taskIds as $relatedTaskId) {
            $task = $this->taskRepository->findOrFail($relatedTaskId);
            $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);
        }

        $taskGroup = TaskGroupModel::create(name: $model->name, ownerId: $userId);
        $savedTaskGroup = $this->taskGroupRepository->save($taskGroup);
        $this->taskGroupLifecycleService->afterCreated($savedTaskGroup);

        foreach ($model->taskIds as $relatedTaskId) {
            $this->taskGroupRepository->attachTask($savedTaskGroup->id, $relatedTaskId);
        }

        if ($model->taskIds !== []) {
            $this->recalculationDispatcher->requestByGroupIds([$savedTaskGroup->id]);
        }

        return TaskGroupViewModelMapper::fromModel($savedTaskGroup);
    }
}
