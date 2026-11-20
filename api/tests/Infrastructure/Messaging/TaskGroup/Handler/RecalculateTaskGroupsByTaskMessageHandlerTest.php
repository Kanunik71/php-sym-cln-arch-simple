<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Messaging\TaskGroup\Handler;

use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;
use App\Application\Event\TaskGroup\TaskGroupRecalculationByTaskRequestedEvent;
use App\Application\Port\TaskGroup\TaskGroupLockInterface;
use App\Application\Service\TaskGroup\Lifecycle\TaskGroupRecalculationService;
use App\Infrastructure\Messaging\TaskGroup\Handler\RecalculateTaskGroupsByTaskMessageHandler;
use App\Tests\DatabaseTestCase;

final class RecalculateTaskGroupsByTaskMessageHandlerTest extends DatabaseTestCase
{
    public function testRecalculatesGroupsOfTask(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createFinishedTask($user->getId(), 'Build feature', 60);
        $group = $this->bed->createTaskGroup(
            ownerId: $user->getId(),
            name: 'Release',
            status: TaskGroupStatusEnum::InProgress,
        );
        $this->bed->linkTaskGroupToTask($group, $task->getId());

        ($this->handler())(new TaskGroupRecalculationByTaskRequestedEvent(taskId: $task->getId()));

        $this->bed->clear();
        $this->assertSame(
            TaskGroupStatusEnum::Completed,
            $this->bed->findTaskGroupById($group->getId())?->getStatus(),
        );
    }

    public function testSkipsRecalculationWhileChannelIsLocked(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createFinishedTask($user->getId(), 'Build feature', 60);
        $group = $this->bed->createTaskGroup(
            ownerId: $user->getId(),
            name: 'Release',
            status: TaskGroupStatusEnum::InProgress,
        );
        $this->bed->linkTaskGroupToTask($group, $task->getId());

        $held = $this->bed->get(TaskGroupLockInterface::class)->acquireByTaskId($task->getId());
        $this->assertNotNull($held);

        ($this->handler())(new TaskGroupRecalculationByTaskRequestedEvent(taskId: $task->getId()));

        $this->bed->clear();
        $this->assertSame(
            TaskGroupStatusEnum::InProgress,
            $this->bed->findTaskGroupById($group->getId())?->getStatus(),
            'Пока канал занят, второй пересчёт запускаться не должен.',
        );

        $held->release();

        ($this->handler())(new TaskGroupRecalculationByTaskRequestedEvent(taskId: $task->getId()));

        $this->bed->clear();
        $this->assertSame(
            TaskGroupStatusEnum::Completed,
            $this->bed->findTaskGroupById($group->getId())?->getStatus(),
        );
    }

    public function testReleasesLockAfterRecalculation(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createFinishedTask($user->getId(), 'Build feature', 60);

        ($this->handler())(new TaskGroupRecalculationByTaskRequestedEvent(taskId: $task->getId()));

        $handle = $this->bed->get(TaskGroupLockInterface::class)->acquireByTaskId($task->getId());
        $this->assertNotNull($handle, 'Лок должен быть освобождён после пересчёта.');

        $handle->release();
    }

    private function handler(): RecalculateTaskGroupsByTaskMessageHandler
    {
        return new RecalculateTaskGroupsByTaskMessageHandler(
            recalculationService: $this->bed->get(TaskGroupRecalculationService::class),
            taskGroupLock: $this->bed->get(TaskGroupLockInterface::class),
        );
    }
}
