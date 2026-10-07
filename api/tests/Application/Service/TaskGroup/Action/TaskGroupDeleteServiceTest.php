<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\TaskGroup\Action;

use App\Application\Service\TaskGroup\Action\TaskGroupDeleteService;
use App\Tests\DatabaseTestCase;

final class TaskGroupDeleteServiceTest extends DatabaseTestCase
{
    public function testDeletesTaskGroup(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');
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
}
