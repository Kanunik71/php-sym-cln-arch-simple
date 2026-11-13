<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\TaskGroup\Action;

use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Enum\Task\TaskUserStatusEnum;
use App\Application\Event\TaskGroup\TaskGroupsRecalculationRequestedEvent;
use App\Application\Exception\Task\TaskNotFoundException;
use App\Application\Exception\Task\UnauthorizedTaskAccessException;
use App\Application\Model\Task\TaskUserModel;
use App\Application\Model\TaskGroup\Action\TaskGroupCreateModel;
use App\Application\Service\TaskGroup\Action\TaskGroupCreateService;
use App\Shared\Utils\UidUtils;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Common\AssertMessengerTrait;

final class TaskGroupCreateServiceTest extends DatabaseTestCase
{
    use AssertMessengerTrait;

    public function testCreatesTaskGroupWithTaskIds(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(ownerId: $user->getId(), name: 'Build feature');
        $otherTask = $this->bed->createTask(ownerId: $user->getId(), name: 'Other feature');
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

    public function testThrowsWhenTaskUnknown(): void
    {
        $user = $this->bed->seedDefaultUser();

        $this->expectException(TaskNotFoundException::class);

        $this->bed->get(TaskGroupCreateService::class)->execute(
            new TaskGroupCreateModel(name: 'Release 1.0', taskIds: [UidUtils::nil()]),
            userId: $user->getId(),
        );
    }

    public function testThrowsWhenTaskIsNotAccessible(): void
    {
        $owner = $this->bed->seedDefaultUser();
        $otherUser = $this->bed->createUser(email: 'other@example.com');
        // задача с участниками — иначе она открыта всем
        $task = $this->bed->createTask(
            ownerId: $owner->getId(),
            name: 'Foreign task',
            users: [TaskUserModel::create($owner->getId(), TaskUserStatusEnum::Active)],
        );

        $this->expectException(UnauthorizedTaskAccessException::class);

        $this->bed->get(TaskGroupCreateService::class)->execute(
            new TaskGroupCreateModel(name: 'Release 1.0', taskIds: [$task->getId()]),
            userId: $otherUser->getId(),
        );
    }
}
