<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait\Model;

use App\Application\Model\Task\TaskModel;
use App\Application\Model\Task\Read\TaskViewModel;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Shared\Utils\DateUtils;
use App\Tests\Support\TestBed;

trait AssertsTaskModelTrait
{
    /** @var TestBed */
    protected $bed;

    private function assertTaskModelMatches(TaskModel $task, TaskViewModel $model): void
    {
        $users = $this->bed->taskRepository()->listUsers($task->id);

        $this->assertSame($task->id, $model->id);
        $this->assertSame($task->name, $model->name);
        $this->assertSame($task->parentId, $model->parentId);
        $this->assertSame($task->estimateTime, $model->estimateTime);
        $this->assertSame($task->status, $model->status);
        $this->assertSame(DateUtils::toAtom($task->createdAt), $model->createdAt);
        $this->assertSame($task->cancelReason, $model->cancelReason);
        $this->assertSame(DateUtils::toAtomOrNull($task->finishedDate), $model->finishedDate);
        $this->assertSame(DateUtils::toAtomOrNull($task->cancellationDate), $model->cancellationDate);

        $expectedUsers = [];
        foreach ($users as $taskUser) {
            $expectedUsers[] = [
                'userId' => $taskUser->userId,
                'status' => $taskUser->status,
            ];
        }

        $actualUsers = [];
        foreach ($model->users as $user) {
            $actualUsers[] = [
                'userId' => $user->userId,
                'status' => $user->status,
            ];
        }

        $this->assertSame($expectedUsers, $actualUsers);
    }
}
