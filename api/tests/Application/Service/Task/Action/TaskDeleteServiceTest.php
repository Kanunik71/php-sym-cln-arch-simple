<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Task\Action;

use App\Application\Enum\Task\TaskUserStatusEnum;
use App\Application\Exception\Task\UnauthorizedTaskAccessException;
use App\Application\Model\Task\TaskUserModel;
use App\Application\Service\Task\Action\TaskDeleteService;
use App\Tests\DatabaseTestCase;

final class TaskDeleteServiceTest extends DatabaseTestCase
{
    public function testOwnerDeletesTaskWithoutBeingParticipant(): void
    {
        $owner = $this->bed->seedDefaultUser();
        $participant = $this->bed->createUser(email: 'participant@example.com');
        $task = $this->bed->createTask(
            ownerId: $owner->getId(),
            name: 'Learn Symfony',
            users: [TaskUserModel::create($participant->getId(), TaskUserStatusEnum::Active)],
        );
        $taskId = $task->getId();

        $this->bed->get(TaskDeleteService::class)->execute(taskId: $taskId, userId: $owner->getId());

        $this->bed->clear();
        $this->assertNull($this->bed->findTaskById($taskId));
    }

    public function testParticipantWhoIsNotOwnerCannotDelete(): void
    {
        $owner = $this->bed->seedDefaultUser();
        $participant = $this->bed->createUser(email: 'participant@example.com');
        $task = $this->bed->createTask(
            ownerId: $owner->getId(),
            name: 'Learn Symfony',
            users: [TaskUserModel::create($participant->getId(), TaskUserStatusEnum::Active)],
        );

        $this->expectException(UnauthorizedTaskAccessException::class);

        $this->bed->get(TaskDeleteService::class)->execute(
            taskId: $task->getId(),
            userId: $participant->getId(),
        );
    }

    public function testThrowsWhenDeletingAnotherUsersTask(): void
    {
        $owner = $this->bed->seedDefaultUser();
        $task = $this->bed->createActiveTask($owner->getId(), 'Owner task');
        $other = $this->bed->createUser(email: 'other@example.com');

        $this->expectException(UnauthorizedTaskAccessException::class);

        $this->bed->get(TaskDeleteService::class)->execute(taskId: $task->getId(), userId: $other->getId());
    }
}
