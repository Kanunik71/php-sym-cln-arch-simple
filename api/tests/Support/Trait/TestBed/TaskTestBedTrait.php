<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait\TestBed;

use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Enum\Task\TaskUserStatusEnum;
use App\Application\Model\Task\TaskModel;
use App\Application\Model\Task\TaskUserModel;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;

trait TaskTestBedTrait
{
    public function taskRepository(): TaskRepositoryInterface
    {
        return $this->tasks;
    }

    /**
     * @param list<TaskUserModel> $users
     */
    public function createTask(
        string $name = 'Task',
        TaskStatusEnum $status = TaskStatusEnum::Initial,
        ?string $parentId = null,
        ?int $estimateTime = null,
        ?string $cancelReason = null,
        ?DateTimeImmutable $finishedDate = null,
        ?DateTimeImmutable $cancellationDate = null,
        array $users = [],
        ?string $id = null,
        ?DateTimeImmutable $createdAt = null,
    ): TaskModel {
        $now = $createdAt ?? new DateTimeImmutable();

        return $this->tasks->save(
            new TaskModel(
                id: $id ?? UidUtils::generateString(),
                name: $name,
                parentId: $parentId,
                estimateTime: $estimateTime,
                status: $status,
                createdAt: $now,
                updatedAt: $now,
                cancelReason: $cancelReason,
                finishedDate: $finishedDate,
                cancellationDate: $cancellationDate,
            ),
            users: $users,
        );
    }

    public function createActiveTask(
        string $userId,
        string $name = 'Task',
        int $estimateTime = 60,
        ?string $parentId = null,
    ): TaskModel {
        return $this->createTask(
            name: $name,
            status: TaskStatusEnum::Active,
            parentId: $parentId,
            estimateTime: $estimateTime,
            users: [TaskUserModel::create($userId, TaskUserStatusEnum::Active)],
        );
    }

    public function createFinishedTask(
        string $userId,
        string $name = 'Task',
        int $estimateTime = 60,
        ?string $parentId = null,
        ?string $id = null,
    ): TaskModel {
        return $this->createTask(
            name: $name,
            status: TaskStatusEnum::Finished,
            parentId: $parentId,
            estimateTime: $estimateTime,
            finishedDate: new DateTimeImmutable(),
            users: [TaskUserModel::create($userId, TaskUserStatusEnum::Finished)],
            id: $id,
        );
    }

    public function createCanceledTask(
        string $userId,
        string $cancelReason,
        string $name = 'Task',
        int $estimateTime = 60,
        ?string $parentId = null,
    ): TaskModel {
        return $this->createTask(
            name: $name,
            status: TaskStatusEnum::Canceled,
            parentId: $parentId,
            estimateTime: $estimateTime,
            cancelReason: $cancelReason,
            cancellationDate: new DateTimeImmutable(),
            users: [TaskUserModel::create($userId, TaskUserStatusEnum::Active)],
        );
    }

    public function findTaskById(string $id): ?TaskModel
    {
        return $this->tasks->findById($id);
    }
}
