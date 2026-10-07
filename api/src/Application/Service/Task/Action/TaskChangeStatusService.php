<?php

declare(strict_types=1);

namespace App\Application\Service\Task\Action;

use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Enum\Task\TaskUserStatusEnum;
use App\Application\Model\Task\Action\TaskChangeStatusModel;
use App\Application\Model\Task\TaskModel;
use App\Application\Model\Task\Read\TaskViewModel;
use App\Application\Policy\Task\ChangeTaskStatusPolicy;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Service\Task\Lifecycle\TaskLifecycleService;
use App\Application\Service\Task\Lifecycle\TaskUserListService;
use InvalidArgumentException;

final readonly class TaskChangeStatusService
{
    public function __construct(
        private ChangeTaskStatusPolicy $changeTaskStatusPolicy,
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private TaskLifecycleService $taskLifecycleService,
    ) {
    }

    public function execute(TaskChangeStatusModel $model, string $taskId, string $userId): TaskViewModel
    {
        $status = $this->changeTaskStatusPolicy->resolveTargetStatus($model);

        $task = $this->taskRepository->findOrFail($taskId);

        $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);

        $users = $this->taskRepository->listUsers($task->id);
        $previousUserIds = TaskUserListService::ids($users);

        [$updatedTask, $usersToSave] = $this->applyStatusChange($task, $users, $status, $model);

        $savedTask = $this->taskRepository->save($updatedTask, $usersToSave);

        $this->taskLifecycleService->afterTaskPersisted(
            task: $savedTask,
            actorUserId: $userId,
            previousUserIds: $previousUserIds,
            currentUserIds: TaskUserListService::ids($usersToSave),
        );

        return $this->taskRepository->findViewOrFail($savedTask->id);
    }

    /**
     * @param list<\App\Application\Model\Task\TaskUserModel> $users
     *
     * @return array{0: TaskModel, 1: list<\App\Application\Model\Task\TaskUserModel>}
     */
    private function applyStatusChange(
        TaskModel $task,
        array $users,
        TaskStatusEnum $status,
        TaskChangeStatusModel $model,
    ): array {
        return match ($status) {
            TaskStatusEnum::Active => $this->activate($task, $users, $model),
            TaskStatusEnum::Canceled => [
                $task->canceled(cancelReason: $this->changeTaskStatusPolicy->requireCancelReason($model)),
                $users,
            ],
            TaskStatusEnum::Finished => [$task->finished(), $users],
            TaskStatusEnum::Initial => throw new InvalidArgumentException(
                'Invalid status. Allowed values: Active, Canceled, Finished.',
            ),
        };
    }

    /**
     * @param list<\App\Application\Model\Task\TaskUserModel> $users
     *
     * @return array{0: TaskModel, 1: list<\App\Application\Model\Task\TaskUserModel>}
     */
    private function activate(
        TaskModel $task,
        array $users,
        TaskChangeStatusModel $model,
    ): array {
        $updated = $task->activated(
            estimateTime: $this->changeTaskStatusPolicy->requireEstimateTime($model),
        );

        $assignUserId = $model->assignUserId;
        if ($assignUserId !== null && $assignUserId !== '') {
            $users = TaskUserListService::assign($users, $assignUserId, TaskUserStatusEnum::Active);
        }

        return [$updated, $users];
    }
}
