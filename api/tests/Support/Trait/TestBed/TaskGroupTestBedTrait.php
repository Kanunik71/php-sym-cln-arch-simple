<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait\TestBed;

use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;
use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Application\Service\TaskGroup\Lifecycle\TaskGroupRecalculationService;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;

trait TaskGroupTestBedTrait
{
    public function taskGroupRepository(): TaskGroupRepositoryInterface
    {
        return $this->taskGroups;
    }

    public function createTaskGroup(
        string $ownerId,
        string $name = 'Task group',
        TaskGroupStatusEnum $status = TaskGroupStatusEnum::Initial,
        ?string $id = null,
        ?DateTimeImmutable $createdAt = null,
    ): TaskGroupModel {
        return $this->taskGroups->save(new TaskGroupModel(
            id: $id ?? UidUtils::generateString(),
            ownerId: $ownerId,
            name: $name,
            status: $status,
            createdAt: $createdAt ?? new DateTimeImmutable(),
        ));
    }

    public function createTaskGroupForTask(
        string $taskId,
        string $ownerId,
        string $name = 'Task group',
    ): TaskGroupModel {
        $task = $this->tasks->findOrFail($taskId);

        $taskGroup = $this->createTaskGroup(
            ownerId: $ownerId,
            name: $name,
            status: TaskGroupRecalculationService::statusFromMembership(1, $task->status->keepsAssetInProgress()),
        );

        $this->taskGroups->attachTask($taskGroup->id, $taskId);

        return $taskGroup;
    }

    public function linkTaskGroupToTask(TaskGroupModel $taskGroup, string $taskId): TaskGroupModel
    {
        $this->taskGroups->attachTask($taskGroup->id, $taskId);

        return $taskGroup;
    }

    public function findTaskGroupById(string $id): ?TaskGroupModel
    {
        return $this->taskGroups->findById($id);
    }
}
