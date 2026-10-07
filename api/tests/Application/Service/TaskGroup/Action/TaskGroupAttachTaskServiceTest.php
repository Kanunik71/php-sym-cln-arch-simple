<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\TaskGroup\Action;

use App\Application\Service\TaskGroup\Action\TaskGroupAttachTaskService;
use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Event\TaskGroup\TaskGroupsRecalculationRequestedEvent;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Common\AssertMessengerTrait;

final class TaskGroupAttachTaskServiceTest extends DatabaseTestCase
{
    use AssertMessengerTrait;

    public function testAttachesTaskToTaskGroup(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');
        $otherTask = $this->bed->createTask(name: 'Other feature');
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
            TaskGroupsRecalculationRequestedEvent::class,
            predicate: fn (TaskGroupsRecalculationRequestedEvent $event): bool => $event->taskGroupIds === [$group->getId()],
        );
        $this->bed->get(TaskGroupRecalculationDispatcher::class)->releaseGroupIdsLock([$group->getId()]);
    }

    public function testAttachIsIdempotent(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');
        $group = $this->bed->createTaskGroupForTask($task->getId(), $user->getId(), 'Release 1.0');

        $attachTaskToTaskGroup = $this->bed->get(TaskGroupAttachTaskService::class);

        $result = $attachTaskToTaskGroup->execute(
            userId: $user->getId(),
            taskId: $task->getId(),
            taskGroupId: $group->getId(),
        );

        $this->assertSame($group->getId(), $result->id);
        $this->assertDispatched(TaskGroupsRecalculationRequestedEvent::class, times: 0);
    }
}
