<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\TaskGroup\Query;

use App\Application\Service\TaskGroup\Query\TaskGroupQueryService;
use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsTaskGroupModelTrait;

final class TaskGroupQueryServiceTest extends DatabaseTestCase
{
    use AssertsTaskGroupModelTrait;

    public function testReturnsMappedTaskGroup(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createFinishedTask($user->getId(), 'Build feature', 60);
        $group = $this->bed->createTaskGroupForTask($task->getId(), $user->getId(), 'Release 1.0');

        $getTaskGroup = $this->bed->get(TaskGroupQueryService::class);

        $result = $getTaskGroup->execute(
            userId: $user->getId(),
            taskId: $task->getId(),
            taskGroupId: $group->getId(),
        );

        $this->assertSame($group->getId(), $result->id);
        $this->assertSame('Release 1.0', $result->name);
        $this->assertSame(TaskGroupStatusEnum::Completed, $result->status);
        $this->assertTaskGroupModelMatches($group, $result);
    }
}
