<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\TaskGroup\Query;

use App\Application\Service\TaskGroup\Query\TaskGroupListQueryService;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsTaskGroupModelTrait;

final class TaskGroupListQueryServiceTest extends DatabaseTestCase
{
    use AssertsTaskGroupModelTrait;

    public function testReturnsMappedTaskGroups(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');
        $group = $this->bed->createTaskGroupForTask($task->getId(), $user->getId(), 'Release 1.0');

        $listTaskGroups = $this->bed->get(TaskGroupListQueryService::class);

        $groups = $listTaskGroups->execute(
            userId: $user->getId(),
            taskId: $task->getId(),
        );

        $this->assertCount(1, $groups);
        $this->assertSame('Release 1.0', $groups[0]->name);
        $this->assertTaskGroupModelMatches($group, $groups[0]);
    }
}
