<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\TaskGroup\Action;

use App\Application\Event\TaskGroup\TaskGroupsRecalculationByGroupIdsRequestedEvent;
use App\Application\Exception\Task\TaskNotFoundException;
use App\Application\Exception\TaskGroup\TaskGroupNotFoundException;
use App\Application\Exception\TaskGroup\UnauthorizedTaskGroupAccessException;
use App\Application\Service\TaskGroup\Action\TaskGroupAttachTaskService;
use App\Shared\Utils\UidUtils;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Common\AssertMessengerTrait;

final class TaskGroupAttachTaskServiceTest extends DatabaseTestCase
{
    use AssertMessengerTrait;

    public function testAttachesTaskToTaskGroup(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(ownerId: $user->getId(), name: 'Build feature');
        $otherTask = $this->bed->createTask(ownerId: $user->getId(), name: 'Other feature');
        $group = $this->bed->createTaskGroupForTask($otherTask->getId(), $user->getId(), 'Release 1.0');

        $attachTaskToTaskGroup = $this->bed->get(TaskGroupAttachTaskService::class);

        $result = $attachTaskToTaskGroup->execute(
            userId: $user->getId(),
            taskId: $task->getId(),
            taskGroupId: $group->getId(),
        );

        $this->assertSame($group->getId(), $result->id);
        $this->assertTrue(
            $this->bed->taskGroupRepository()->containsTask($group->getId(), $task->getId()),
        );

        $this->assertDispatched(
            TaskGroupsRecalculationByGroupIdsRequestedEvent::class,
            predicate: fn (TaskGroupsRecalculationByGroupIdsRequestedEvent $event): bool => $event->taskGroupIds === [$group->getId()],
        );
    }

    public function testRejectsNonOwner(): void
    {
        $owner = $this->bed->seedDefaultUser();
        $otherUser = $this->bed->createUser();
        $task = $this->bed->createTask(ownerId: $owner->getId(), name: 'Build feature');
        $groupedTask = $this->bed->createTask(ownerId: $owner->getId(), name: 'Other feature');
        $group = $this->bed->createTaskGroupForTask($groupedTask->getId(), $owner->getId(), 'Release 1.0');

        $attachTaskToTaskGroup = $this->bed->get(TaskGroupAttachTaskService::class);

        $this->expectException(UnauthorizedTaskGroupAccessException::class);

        $attachTaskToTaskGroup->execute(
            userId: $otherUser->getId(),
            taskId: $task->getId(),
            taskGroupId: $group->getId(),
        );
    }

    public function testAttachIsIdempotent(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(ownerId: $user->getId(), name: 'Build feature');
        $group = $this->bed->createTaskGroupForTask($task->getId(), $user->getId(), 'Release 1.0');

        $attachTaskToTaskGroup = $this->bed->get(TaskGroupAttachTaskService::class);

        $result = $attachTaskToTaskGroup->execute(
            userId: $user->getId(),
            taskId: $task->getId(),
            taskGroupId: $group->getId(),
        );

        $this->assertSame($group->getId(), $result->id);
        $this->assertDispatched(TaskGroupsRecalculationByGroupIdsRequestedEvent::class, times: 0);
    }

    public function testThrowsWhenTaskGroupUnknown(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(ownerId: $user->getId(), name: 'Build feature');

        $this->expectException(TaskGroupNotFoundException::class);

        $this->bed->get(TaskGroupAttachTaskService::class)->execute(
            userId: $user->getId(),
            taskId: $task->getId(),
            taskGroupId: UidUtils::nil(),
        );
    }

    public function testThrowsWhenTaskUnknown(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(ownerId: $user->getId(), name: 'Build feature');
        $group = $this->bed->createTaskGroupForTask($task->getId(), $user->getId(), 'Release 1.0');

        $this->expectException(TaskNotFoundException::class);

        $this->bed->get(TaskGroupAttachTaskService::class)->execute(
            userId: $user->getId(),
            taskId: UidUtils::nil(),
            taskGroupId: $group->getId(),
        );
    }
}
