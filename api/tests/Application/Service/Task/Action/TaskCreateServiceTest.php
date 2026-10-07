<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Task\Action;

use App\Application\Service\Task\Action\TaskCreateService;
use App\Application\Model\Task\Action\TaskCreateModel;
use App\Application\Service\Task\Query\TaskListQueryService;
use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Event\Task\TaskCreated;
use App\Application\Exception\Task\TaskNotFoundException;
use App\Shared\Utils\UidUtils;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Common\AssertMessengerTrait;
use App\Tests\Support\Trait\Model\AssertsTaskModelTrait;

final class TaskCreateServiceTest extends DatabaseTestCase
{
    use AssertMessengerTrait;
    use AssertsTaskModelTrait;

    public function testCreatesTask(): void
    {
        $user = $this->bed->seedDefaultUser();
        $create = new TaskCreateModel(name: 'Learn Symfony');

        $createTask = $this->bed->get(TaskCreateService::class);

        $result = $createTask->execute($create, userId: $user->getId());

        $this->assertSame($create->name, $result->name);
        $this->assertSame(TaskStatusEnum::Initial, $result->status);
        $this->assertSame([], $result->users);
        $this->assertNull($result->estimateTime);
        $this->assertNull($result->parentId);
        $this->assertNotEmpty($result->id);

        $this->assertDispatched(
            TaskCreated::class,
            predicate: fn (TaskCreated $event): bool => $event->taskId === $result->id
                && $event->name === $create->name
                && $event->estimateTime === $create->estimateTime
                && $event->parentId === $create->parentId,
        );

        $this->bed->clear();
        $persisted = $this->bed->findTaskById($result->id);
        $this->assertNotNull($persisted);
        $this->assertTaskModelMatches($persisted, $result);
    }

    public function testCreateInvalidatesTaskListCache(): void
    {
        $user = $this->bed->seedDefaultUser();
        $listTasks = $this->bed->get(TaskListQueryService::class);
        $createTask = $this->bed->get(TaskCreateService::class);

        $this->assertCount(0, $listTasks->execute(userId: $user->getId()));

        $result = $createTask->execute(
            new TaskCreateModel(name: 'Cached refresh'),
            userId: $user->getId(),
        );

        $tasks = $listTasks->execute(userId: $user->getId());

        $this->assertCount(1, $tasks);
        $this->assertSame($result->id, $tasks[0]->id);
        $this->assertSame($result->name, $tasks[0]->name);
    }

    public function testCreatesChildTaskWithParentId(): void
    {
        $user = $this->bed->seedDefaultUser();
        $parentTask = $this->bed->createTask(name: 'Parent task');

        $create = new TaskCreateModel(
            name: 'Child task',
            parentId: $parentTask->getId(),
        );

        $createTask = $this->bed->get(TaskCreateService::class);

        $result = $createTask->execute($create, userId: $user->getId());

        $this->assertSame($create->name, $result->name);
        $this->assertSame($create->parentId, $result->parentId);

        $this->assertDispatched(
            TaskCreated::class,
            predicate: fn (TaskCreated $event): bool => $event->taskId === $result->id
                && $event->parentId === $parentTask->getId(),
        );
    }

    public function testThrowsWhenParentUnknown(): void
    {
        $user = $this->bed->seedDefaultUser();
        $createTask = $this->bed->get(TaskCreateService::class);

        $this->expectException(TaskNotFoundException::class);

        $createTask->execute(
            new TaskCreateModel(name: 'Orphan child', parentId: UidUtils::nil()),
            userId: $user->getId(),
        );
    }
}
