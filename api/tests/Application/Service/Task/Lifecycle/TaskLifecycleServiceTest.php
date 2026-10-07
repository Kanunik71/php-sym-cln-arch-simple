<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Task\Lifecycle;

use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Service\Task\Lifecycle\TaskLifecycleService;
use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Event\Task\TaskCreated;
use App\Application\Event\TaskGroup\TaskGroupsRecalculationRequestedEvent;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Cache\AssertTaskCacheTrait;
use App\Tests\Support\Trait\Common\AssertMessengerTrait;

final class TaskLifecycleServiceTest extends DatabaseTestCase
{
    use AssertMessengerTrait;
    use AssertTaskCacheTrait;

    public function testAfterTaskPersistedOnCreateDispatchesTaskCreatedAndInvalidatesActorListCache(): void
    {
        $user = $this->bed->seedDefaultUser();
        $service = $this->bed->get(TaskLifecycleService::class);

        $this->warmTaskUserListCache($user->getId(), []);

        $task = $this->bed->createTask(name: 'Learn Symfony');

        $service->afterTaskPersisted(
            task: $task,
            actorUserId: $user->getId(),
            created: true,
        );

        $this->assertDispatched(
            TaskCreated::class,
            predicate: fn (TaskCreated $event): bool => $event->taskId === $task->getId()
                && $event->name === $task->getName()
                && $event->estimateTime === $task->getEstimateTime()
                && $event->parentId === $task->getParentId(),
        );

        $tasks = $this->assertTaskUserListCacheInvalidated(
            $user->getId(),
            fn () => $this->bed->taskRepository()->listByUserId($user->getId()),
        );

        $this->assertCount(1, $tasks);
        $this->assertSame($task->getId(), $tasks[0]->getId());
        $this->assertSame($task->getName(), $tasks[0]->getName());
    }

    public function testAfterTaskPersistedOnUpdateInvalidatesListsAndRequestsRecalculation(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createActiveTask($user->getId(), 'Active task', 60);
        $service = $this->bed->get(TaskLifecycleService::class);

        $this->assertSame(TaskStatusEnum::Active, $this->bed->findTaskById($task->getId())?->getStatus());

        $this->warmTaskUserListCache($user->getId());

        $finishedTask = $this->bed->createFinishedTask(
            userId: $user->getId(),
            name: 'Active task',
            estimateTime: 60,
            id: $task->getId(),
        );

        $service->afterTaskPersisted(
            task: $finishedTask,
            actorUserId: $user->getId(),
            previousUserIds: [$user->getId()],
        );

        $this->assertDispatched(
            TaskGroupsRecalculationRequestedEvent::class,
            predicate: fn (TaskGroupsRecalculationRequestedEvent $event): bool => $event->taskId === $task->getId(),
        );
        $this->bed->get(TaskGroupRecalculationDispatcher::class)->releaseTaskLock($task->getId());

        $this->assertTaskUserListCacheInvalidated($user->getId());

        $persisted = $this->bed->findTaskById($task->getId());
        $this->assertNotNull($persisted);
        $this->assertSame(TaskStatusEnum::Finished, $persisted->getStatus());
    }
}
