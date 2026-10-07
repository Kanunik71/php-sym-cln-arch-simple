<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\TaskGroup\Action;

use App\Application\Service\TaskGroup\Action\TaskGroupCreateService;
use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Model\TaskGroup\Action\TaskGroupCreateModel;
use App\Application\Event\TaskGroup\TaskGroupsRecalculationRequestedEvent;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Common\AssertMessengerTrait;

final class TaskGroupCreateServiceTest extends DatabaseTestCase
{
    use AssertMessengerTrait;

    public function testCreatesTaskGroupWithTaskIds(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');
        $otherTask = $this->bed->createTask(name: 'Other feature');
        $create = new TaskGroupCreateModel(
            name: 'Release 1.0',
            taskIds: [$task->getId(), $otherTask->getId()],
        );

        $createTaskGroup = $this->bed->get(TaskGroupCreateService::class);

        $result = $createTaskGroup->execute(
            $create,
            userId: $user->getId(),
        );

        $this->assertSame($create->name, $result->name);
        $this->assertSame($user->getId(), $result->ownerId);
        $this->assertTrue(
            $this->bed->taskGroupRepository()->containsTask($result->id, $task->getId()),
        );
        $this->assertTrue(
            $this->bed->taskGroupRepository()->containsTask($result->id, $otherTask->getId()),
        );

        $this->assertDispatched(
            TaskGroupsRecalculationRequestedEvent::class,
            predicate: fn (TaskGroupsRecalculationRequestedEvent $event): bool => $event->taskGroupIds === [$result->id],
        );
        $this->bed->get(TaskGroupRecalculationDispatcher::class)->releaseGroupIdsLock([$result->id]);
    }

    public function testCreatesEmptyTaskGroupWithoutRecalculation(): void
    {
        $user = $this->bed->seedDefaultUser();
        $create = new TaskGroupCreateModel(name: 'Empty group');

        $createTaskGroup = $this->bed->get(TaskGroupCreateService::class);

        $result = $createTaskGroup->execute(
            $create,
            userId: $user->getId(),
        );

        $this->assertSame($create->name, $result->name);
        $this->assertSame($user->getId(), $result->ownerId);
        $this->assertDispatched(TaskGroupsRecalculationRequestedEvent::class, times: 0);
    }
}
