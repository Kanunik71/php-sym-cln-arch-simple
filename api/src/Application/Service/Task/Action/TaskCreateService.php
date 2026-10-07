<?php

declare(strict_types=1);

namespace App\Application\Service\Task\Action;

use App\Application\Model\Task\Action\TaskCreateModel;
use App\Application\Model\Task\TaskModel;
use App\Application\Model\Task\Read\TaskViewModel;
use App\Application\Policy\Task\CreateTaskPolicy;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Service\Task\Lifecycle\TaskLifecycleService;

final readonly class TaskCreateService
{
    public function __construct(
        private CreateTaskPolicy $createTaskPolicy,
        private TaskRepositoryInterface $taskRepository,
        private TaskLifecycleService $taskLifecycleService,
    ) {
    }

    public function execute(TaskCreateModel $model, string $userId): TaskViewModel
    {
        $this->createTaskPolicy->assertParentExistsIo($model->parentId);

        $task = TaskModel::create(
            name: $model->name,
            estimateTime: $model->estimateTime,
            parentId: $model->parentId,
        );

        $savedTask = $this->taskRepository->save($task, users: []);

        $this->taskLifecycleService->afterTaskPersisted(
            task: $savedTask,
            actorUserId: $userId,
            created: true,
        );

        return $this->taskRepository->findViewOrFail($savedTask->id);
    }
}
