<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Task\Lifecycle;

use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Event\Task\TaskCreated;
use App\Application\Event\TaskGroup\TaskGroupsRecalculationRequestedEvent;
use App\Application\Service\Task\Lifecycle\TaskLifecycleService;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Common\AssertMessengerTrait;

final class TaskLifecycleServiceTest extends DatabaseTestCase
{
    use AssertMessengerTrait;

    public function testAfterTaskPersistedOnCreateDispatchesTaskCreated(): void
    {
        $user = $this->bed->seedDefaultUser();
        $service = $this->bed->get(TaskLifecycleService::class);
        $task = $this->bed->createTask(ownerId: $user->getId(), name: 'Learn Symfony');

        $service->afterTaskPersisted(task: $task, created: true);

        $this->assertDispatched(
            TaskCreated::class,
            predicate: fn (TaskCreated $event): bool => $event->taskId === $task->getId()
                && $event->name === $task->getName()
                && $event->estimateTime === $task->getEstimateTime()
                && $event->parentId === $task->getParentId(),
        );
    }

    public function testAfterTaskPersistedOnUpdateRequestsRecalculation(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createActiveTask($user->getId(), 'Active task', 60);
        $service = $this->bed->get(TaskLifecycleService::class);

        $this->assertSame(TaskStatusEnum::Active, $this->bed->findTaskById($task->getId())?->getStatus());

        $finishedTask = $this->bed->createFinishedTask(
            userId: $user->getId(),
            name: 'Active task',
            estimateTime: 60,
            id: $task->getId(),
        );

        $service->afterTaskPersisted(task: $finishedTask);

        $this->assertDispatched(
            TaskGroupsRecalculationRequestedEvent::class,
            predicate: fn (TaskGroupsRecalculationRequestedEvent $event): bool => $event->taskId === $task->getId(),
        );
        $this->bed->get(TaskGroupRecalculationDispatcher::class)->releaseTaskLock($task->getId());

        $this->assertSame(TaskStatusEnum::Finished, $this->bed->findTaskById($task->getId())->getStatus());
    }
}
