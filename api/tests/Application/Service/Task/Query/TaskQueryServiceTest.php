<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Task\Query;

use App\Application\Enum\Task\TaskUserStatusEnum;
use App\Application\Exception\Task\UnauthorizedTaskAccessException;
use App\Application\Model\Task\TaskUserModel;
use App\Application\Service\Task\Query\TaskQueryService;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsTaskModelTrait;

final class TaskQueryServiceTest extends DatabaseTestCase
{
    use AssertsTaskModelTrait;

    public function testReturnsMappedTask(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(ownerId: $user->getId(), name: 'Learn Symfony');

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

    public function testOwnerWithoutMembershipCannotRead(): void
    {
        $owner = $this->bed->seedDefaultUser();
        $participant = $this->bed->createUser(email: 'participant@example.com');
        $task = $this->bed->createTask(
            ownerId: $owner->getId(),
            name: 'Closed',
            users: [TaskUserModel::create($participant->getId(), TaskUserStatusEnum::Active)],
        );

        $result = $this->bed->get(TaskQueryService::class)->execute(
            taskId: $task->getId(),
            userId: $participant->getId(),
        );
        $this->assertSame($task->getId(), $result->id);

        $this->expectException(UnauthorizedTaskAccessException::class);

        $this->bed->get(TaskQueryService::class)->execute(
            taskId: $task->getId(),
            userId: $owner->getId(),
        );
    }
}
