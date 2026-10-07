<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Task\Query;

use App\Application\Service\Task\Query\TaskListQueryService;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsTaskModelTrait;

final class TaskListQueryServiceTest extends DatabaseTestCase
{
    use AssertsTaskModelTrait;

    public function testReturnsMappedTasks(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Learn Symfony');

        $listTasks = $this->bed->get(TaskListQueryService::class);

        $tasks = $listTasks->execute(userId: $user->getId());

        $this->assertCount(1, $tasks);
        $this->assertSame('Learn Symfony', $tasks[0]->name);
        $this->assertTaskModelMatches($task, $tasks[0]);
    }
}
