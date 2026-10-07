<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Task\Action;

use App\Application\Service\Task\Action\TaskDeleteService;
use App\Application\Exception\Task\UnauthorizedTaskAccessException;
use App\Tests\DatabaseTestCase;

final class TaskDeleteServiceTest extends DatabaseTestCase
{
    public function testDeletesTask(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Learn Symfony');
        $taskId = $task->getId();

        $deleteTask = $this->bed->get(TaskDeleteService::class);

        $deleteTask->execute(taskId: $taskId, userId: $user->getId());

        $this->bed->clear();
        $this->assertNull($this->bed->findTaskById($taskId));
    }

    public function testThrowsWhenDeletingAnotherUsersTask(): void
    {
        $owner = $this->bed->seedDefaultUser();
        $task = $this->bed->createActiveTask($owner->getId(), 'Owner task');
        $other = $this->bed->createUser(email: 'other@example.com');

        $deleteTask = $this->bed->get(TaskDeleteService::class);

        $this->expectException(UnauthorizedTaskAccessException::class);

        $deleteTask->execute(taskId: $task->getId(), userId: $other->getId());
    }
}
