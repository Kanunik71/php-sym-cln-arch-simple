<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Messaging\TaskGroup\Handler;

use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;
use App\Application\Event\TaskGroup\TaskGroupRecalculationByGroupIdsRequestedEvent;
use App\Application\Port\TaskGroup\TaskGroupLockInterface;
use App\Application\Service\TaskGroup\Lifecycle\TaskGroupRecalculationService;
use App\Infrastructure\Messaging\TaskGroup\Handler\RecalculateTaskGroupsByGroupIdsMessageHandler;
use App\Tests\DatabaseTestCase;

final class RecalculateTaskGroupsByGroupIdsMessageHandlerTest extends DatabaseTestCase
{
    public function testRecalculatesRequestedGroups(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createFinishedTask($user->getId(), 'Build feature', 60);
        $group = $this->bed->createTaskGroup(
            ownerId: $user->getId(),
            name: 'Release',
            status: TaskGroupStatusEnum::InProgress,
        );
        $this->bed->linkTaskGroupToTask($group, $task->getId());

        ($this->handler())(new TaskGroupRecalculationByGroupIdsRequestedEvent(taskGroupIds: [$group->getId()]));

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

        $held = $this->bed->get(TaskGroupLockInterface::class)->acquireByGroupIds([$group->getId()]);
        $this->assertNotNull($held);

        ($this->handler())(new TaskGroupRecalculationByGroupIdsRequestedEvent(taskGroupIds: [$group->getId()]));

        $this->bed->clear();
        $this->assertSame(
            TaskGroupStatusEnum::InProgress,
            $this->bed->findTaskGroupById($group->getId())?->getStatus(),
            'Пока канал занят, второй пересчёт запускаться не должен.',
        );

        $held->release();

        ($this->handler())(new TaskGroupRecalculationByGroupIdsRequestedEvent(taskGroupIds: [$group->getId()]));

        $this->bed->clear();
        $this->assertSame(
            TaskGroupStatusEnum::Completed,
            $this->bed->findTaskGroupById($group->getId())?->getStatus(),
        );
    }

    public function testReleasesLockAfterRecalculation(): void
    {
        $user = $this->bed->seedDefaultUser();
        $group = $this->bed->createTaskGroup(ownerId: $user->getId(), name: 'Release');

        ($this->handler())(new TaskGroupRecalculationByGroupIdsRequestedEvent(taskGroupIds: [$group->getId()]));

        $handle = $this->bed->get(TaskGroupLockInterface::class)->acquireByGroupIds([$group->getId()]);
        $this->assertNotNull($handle, 'Лок должен быть освобождён после пересчёта.');

        $handle->release();
    }

    private function handler(): RecalculateTaskGroupsByGroupIdsMessageHandler
    {
        return new RecalculateTaskGroupsByGroupIdsMessageHandler(
            recalculationService: $this->bed->get(TaskGroupRecalculationService::class),
            taskGroupLock: $this->bed->get(TaskGroupLockInterface::class),
        );
    }
}
