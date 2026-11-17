<?php

declare(strict_types=1);

namespace App\Tests\Application\Dispatcher\TaskGroup;

use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Event\TaskGroup\TaskGroupsRecalculationByGroupIdsRequestedEvent;
use App\Application\Event\TaskGroup\TaskGroupsRecalculationByTaskRequestedEvent;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Common\AssertMessengerTrait;

final class TaskGroupRecalculationDispatcherTest extends DatabaseTestCase
{
    use AssertMessengerTrait;

    public function testRequestByTaskIdSendsByTaskEventToQueue(): void
    {
        $dispatcher = $this->bed->get(TaskGroupRecalculationDispatcher::class);

        $dispatcher->requestByTaskId('task-1');

        $this->assertDispatched(
            TaskGroupsRecalculationByTaskRequestedEvent::class,
            predicate: fn (TaskGroupsRecalculationByTaskRequestedEvent $event): bool => $event->taskId === 'task-1',
        );
        $this->assertDispatched(TaskGroupsRecalculationByGroupIdsRequestedEvent::class, times: 0);
    }

    public function testRequestByGroupIdsSendsCanonicalByGroupIdsEventToQueue(): void
    {
        $dispatcher = $this->bed->get(TaskGroupRecalculationDispatcher::class);

        $dispatcher->requestByGroupIds(['group-2', 'group-1', 'group-2']);

        $this->assertDispatched(
            TaskGroupsRecalculationByGroupIdsRequestedEvent::class,
            predicate: fn (TaskGroupsRecalculationByGroupIdsRequestedEvent $event): bool => $event->taskGroupIds === ['group-1', 'group-2'],
        );
        $this->assertDispatched(TaskGroupsRecalculationByTaskRequestedEvent::class, times: 0);
    }

    public function testEmptyGroupIdsAreIgnored(): void
    {
        $dispatcher = $this->bed->get(TaskGroupRecalculationDispatcher::class);

        $dispatcher->requestByGroupIds([]);

        $this->assertDispatched(TaskGroupsRecalculationByGroupIdsRequestedEvent::class, times: 0);
    }
}
