<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\TaskGroup\Action;

use App\Application\Exception\TaskGroup\UnauthorizedTaskGroupAccessException;
use App\Application\Service\TaskGroup\Action\TaskGroupRemoveTaskService;
use App\Tests\DatabaseTestCase;

final class TaskGroupRemoveTaskServiceTest extends DatabaseTestCase
{
    public function testRemovesTaskFromTaskGroup(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(ownerId: $user->getId(), name: 'Build feature');
        $group = $this->bed->createTaskGroupForTask($task->getId(), $user->getId(), 'Release 1.0');

        $removeTaskFromTaskGroup = $this->bed->get(TaskGroupRemoveTaskService::class);

        $result = $removeTaskFromTaskGroup->execute(
            userId: $user->getId(),
            taskId: $task->getId(),
            taskGroupId: $group->getId(),
            removeTaskId: $task->getId(),
        );

        $this->assertSame($group->getId(), $result->id);
        $this->assertFalse(
            $this->bed->taskGroupRepository()->containsTask($group->getId(), $task->getId()),
        );
    }

    public function testRejectsNonOwner(): void
    {
        $owner = $this->bed->seedDefaultUser();
        $otherUser = $this->bed->createUser();
        $task = $this->bed->createTask(ownerId: $owner->getId(), name: 'Build feature');
        $group = $this->bed->createTaskGroupForTask($task->getId(), $owner->getId(), 'Release 1.0');

        $removeTaskFromTaskGroup = $this->bed->get(TaskGroupRemoveTaskService::class);

        $this->expectException(UnauthorizedTaskGroupAccessException::class);

        $removeTaskFromTaskGroup->execute(
            userId: $otherUser->getId(),
            taskId: $task->getId(),
            taskGroupId: $group->getId(),
            removeTaskId: $task->getId(),
        );
    }
}
