<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Task\Query;

use App\Application\Service\Task\Query\TaskQueryService;
use App\Application\Exception\Task\UnauthorizedTaskAccessException;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsTaskModelTrait;

final class TaskQueryServiceTest extends DatabaseTestCase
{
    use AssertsTaskModelTrait;

    public function testReturnsMappedTask(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Learn Symfony');

        $getTask = $this->bed->get(TaskQueryService::class);

        $result = $getTask->execute(taskId: $task->getId(), userId: $user->getId());

        $this->assertSame($task->getId(), $result->id);
        $this->assertSame('Learn Symfony', $result->name);
        $this->assertTaskModelMatches($task, $result);
    }

    public function testThrowsWhenAccessingAnotherUsersTask(): void
    {
        $owner = $this->bed->seedDefaultUser();
        $task = $this->bed->createActiveTask($owner->getId(), 'Owner task');
        $other = $this->bed->createUser(email: 'other@example.com');

        $getTask = $this->bed->get(TaskQueryService::class);

        $this->expectException(UnauthorizedTaskAccessException::class);

        $getTask->execute(taskId: $task->getId(), userId: $other->getId());
    }
}
