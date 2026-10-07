<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Task\Action;

use App\Application\Service\Task\Action\TaskChangeStatusService;
use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Model\Task\Action\TaskChangeStatusModel;
use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Enum\Task\TaskUserStatusEnum;
use App\Application\Exception\Task\InvalidTaskStatusTransitionException;
use App\Application\Exception\Task\UnauthorizedTaskAccessException;
use App\Application\Event\TaskGroup\TaskGroupsRecalculationRequestedEvent;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Common\AssertMessengerTrait;
use App\Tests\Support\Trait\Model\AssertsTaskModelTrait;

final class TaskChangeStatusServiceTest extends DatabaseTestCase
{
    use AssertMessengerTrait;
    use AssertsTaskModelTrait;

    public function testActivateAndFinishTask(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Learn Symfony');
        $changeTaskStatus = $this->bed->get(TaskChangeStatusService::class);

        $activate = new TaskChangeStatusModel(
            status: TaskStatusEnum::Active,
            assignUserId: $user->getId(),
            estimateTime: 120,
        );

        $active = $changeTaskStatus->execute(
            $activate,
            taskId: $task->getId(),
            userId: $user->getId(),
        );

        $this->assertSame($activate->status, $active->status);
        $this->assertSame($activate->estimateTime, $active->estimateTime);
        $this->assertCount(1, $active->users);
        $this->assertSame($activate->assignUserId, $active->users[0]->userId);
        $this->assertSame(TaskUserStatusEnum::Active, $active->users[0]->status);
        $this->assertDispatched(
            TaskGroupsRecalculationRequestedEvent::class,
            predicate: fn (TaskGroupsRecalculationRequestedEvent $event): bool => $event->taskId === $task->getId(),
        );
        $this->bed->get(TaskGroupRecalculationDispatcher::class)->releaseTaskLock($task->getId());

        $finish = new TaskChangeStatusModel(status: TaskStatusEnum::Finished);

        $finished = $changeTaskStatus->execute(
            $finish,
            taskId: $task->getId(),
            userId: $user->getId(),
        );

        $this->assertSame($finish->status, $finished->status);
        $this->assertNotNull($finished->finishedDate);
        $this->assertDispatched(
            TaskGroupsRecalculationRequestedEvent::class,
            predicate: fn (TaskGroupsRecalculationRequestedEvent $event): bool => $event->taskId === $task->getId(),
        );
        $this->bed->get(TaskGroupRecalculationDispatcher::class)->releaseTaskLock($task->getId());

        $this->bed->clear();
        $persisted = $this->bed->findTaskById($task->getId());
        $this->assertNotNull($persisted);
        $this->assertTaskModelMatches($persisted, $finished);
    }

    public function testInvalidStatusTransitionIsRejected(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Blocked transition');
        $changeTaskStatus = $this->bed->get(TaskChangeStatusService::class);

        $this->expectException(InvalidTaskStatusTransitionException::class);

        $changeTaskStatus->execute(
            new TaskChangeStatusModel(status: TaskStatusEnum::Finished),
            taskId: $task->getId(),
            userId: $user->getId(),
        );
    }

    public function testCannotChangeStatusOfAnotherUsersTask(): void
    {
        $owner = $this->bed->seedDefaultUser(email: 'owner@example.com');
        $task = $this->bed->createActiveTask($owner->getId(), 'Owner task', 15);
        $other = $this->bed->createUser(email: 'other@example.com');

        $changeTaskStatus = $this->bed->get(TaskChangeStatusService::class);

        $this->expectException(UnauthorizedTaskAccessException::class);

        $changeTaskStatus->execute(
            new TaskChangeStatusModel(status: TaskStatusEnum::Canceled, cancelReason: 'Not mine'),
            taskId: $task->getId(),
            userId: $other->getId(),
        );
    }

    public function testCancelRequiresReasonAndSetsDates(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createActiveTask($user->getId(), 'Cancel me', 45);
        $changeTaskStatus = $this->bed->get(TaskChangeStatusService::class);

        $cancel = new TaskChangeStatusModel(
            status: TaskStatusEnum::Canceled,
            cancelReason: 'Scope changed',
        );

        $result = $changeTaskStatus->execute(
            $cancel,
            taskId: $task->getId(),
            userId: $user->getId(),
        );

        $this->assertSame($cancel->status, $result->status);
        $this->assertSame($cancel->cancelReason, $result->cancelReason);
        $this->assertNotNull($result->cancellationDate);
    }
}
