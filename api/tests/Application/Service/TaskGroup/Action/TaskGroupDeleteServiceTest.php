<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\TaskGroup\Action;

use App\Application\Exception\TaskGroup\UnauthorizedTaskGroupAccessException;
use App\Application\Service\TaskGroup\Action\TaskGroupDeleteService;
use App\Tests\DatabaseTestCase;

final class TaskGroupDeleteServiceTest extends DatabaseTestCase
{
    public function testDeletesTaskGroup(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(ownerId: $user->getId(), name: 'Build feature');
        $group = $this->bed->createTaskGroupForTask($task->getId(), $user->getId(), 'Release 1.0');
        $groupId = $group->getId();

        $deleteTaskGroup = $this->bed->get(TaskGroupDeleteService::class);

        $deleteTaskGroup->execute(
            userId: $user->getId(),
            taskId: $task->getId(),
            taskGroupId: $groupId,
        );

        $this->bed->clear();
        $this->assertNull($this->bed->findTaskGroupById($groupId));
    }

    public function testRejectsNonOwner(): void
    {
        $owner = $this->bed->seedDefaultUser();
        $otherUser = $this->bed->createUser();
        $task = $this->bed->createTask(ownerId: $owner->getId(), name: 'Build feature');
        $group = $this->bed->createTaskGroupForTask($task->getId(), $owner->getId(), 'Release 1.0');

        $deleteTaskGroup = $this->bed->get(TaskGroupDeleteService::class);

        $this->expectException(UnauthorizedTaskGroupAccessException::class);

        $deleteTaskGroup->execute(
            userId: $otherUser->getId(),
            taskId: $task->getId(),
            taskGroupId: $group->getId(),
        );
    }
}
