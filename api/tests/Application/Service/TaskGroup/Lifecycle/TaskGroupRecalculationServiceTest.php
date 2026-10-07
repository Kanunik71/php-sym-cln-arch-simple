<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\TaskGroup\Lifecycle;

use App\Application\Service\TaskGroup\Lifecycle\TaskGroupLifecycleService;
use App\Application\Service\TaskGroup\Lifecycle\TaskGroupRecalculationService;
use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;
use App\Tests\DatabaseTestCase;

final class TaskGroupRecalculationServiceTest extends DatabaseTestCase
{
    public function testRecalculateByTaskIdPersistsCompletedStatus(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createFinishedTask($user->getId(), 'Build feature', 60);
        $taskGroup = $this->bed->createTaskGroup(
            ownerId: $user->getId(),
            name: 'Release',
            status: TaskGroupStatusEnum::InProgress,
        );
        $this->bed->linkTaskGroupToTask($taskGroup, $task->getId());

        $service = $this->bed->get(TaskGroupRecalculationService::class);
        $service->recalculateByTaskId($task->getId());

        $this->bed->clear();
        $persisted = $this->bed->findTaskGroupById($taskGroup->getId());
        $this->assertNotNull($persisted);
        $this->assertSame(TaskGroupStatusEnum::Completed, $persisted->getStatus());
    }

    public function testRecalculateSkipsSaveWhenStatusUnchanged(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createActiveTask($user->getId(), 'Active task', 60);
        $taskGroup = $this->bed->createTaskGroup(
            ownerId: $user->getId(),
            name: 'Release',
            status: TaskGroupStatusEnum::InProgress,
        );
        $this->bed->linkTaskGroupToTask($taskGroup, $task->getId());

        $service = $this->bed->get(TaskGroupRecalculationService::class);
        $service->recalculateByTaskId($task->getId());

        $this->bed->clear();
        $persisted = $this->bed->findTaskGroupById($taskGroup->getId());
        $this->assertNotNull($persisted);
        $this->assertSame(TaskGroupStatusEnum::InProgress, $persisted->getStatus());
    }

    public function testRecalculateByGroupIdsLoadsGroupsInBatches(): void
    {
        $user = $this->bed->seedDefaultUser();

        $taskA = $this->bed->createFinishedTask($user->getId(), 'Task A', 60);
        $taskB = $this->bed->createFinishedTask($user->getId(), 'Task B', 60);
        $taskC = $this->bed->createFinishedTask($user->getId(), 'Task C', 60);

        $groupA = $this->bed->createTaskGroup(ownerId: $user->getId(), name: 'A', status: TaskGroupStatusEnum::InProgress);
        $groupB = $this->bed->createTaskGroup(ownerId: $user->getId(), name: 'B', status: TaskGroupStatusEnum::InProgress);
        $groupC = $this->bed->createTaskGroup(ownerId: $user->getId(), name: 'C', status: TaskGroupStatusEnum::InProgress);
        $this->bed->linkTaskGroupToTask($groupA, $taskA->getId());
        $this->bed->linkTaskGroupToTask($groupB, $taskB->getId());
        $this->bed->linkTaskGroupToTask($groupC, $taskC->getId());

        $service = new TaskGroupRecalculationService(
            $this->bed->taskGroupRepository(),
            $this->bed->get(TaskGroupLifecycleService::class),
            batchSize: 2,
        );
        $service->recalculateByGroupIds([
            $groupA->getId(),
            $groupB->getId(),
            $groupC->getId(),
        ]);

        $this->bed->clear();
        $this->assertSame(TaskGroupStatusEnum::Completed, $this->bed->findTaskGroupById($groupA->getId())?->getStatus());
        $this->assertSame(TaskGroupStatusEnum::Completed, $this->bed->findTaskGroupById($groupB->getId())?->getStatus());
        $this->assertSame(TaskGroupStatusEnum::Completed, $this->bed->findTaskGroupById($groupC->getId())?->getStatus());
    }
}
