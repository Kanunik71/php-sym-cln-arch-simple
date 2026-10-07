<?php

declare(strict_types=1);

namespace App\Tests\Application\Model\Task;

use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Enum\Task\TaskUserStatusEnum;
use App\Application\Exception\Task\InvalidTaskStatusTransitionException;
use App\Application\Model\Task\TaskModel;
use App\Application\Model\Task\TaskUserModel;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TaskModelTest extends TestCase
{
    public function testCreateSetsInitialStatus(): void
    {
        $task = TaskModel::create('Parent task');
        $child = TaskModel::create('Child task', parentId: $task->id);

        $this->assertSame(TaskStatusEnum::Initial, $task->status);
        $this->assertSame($task->id, $child->parentId);
    }

    public function testActivatedTransitionsStatus(): void
    {
        $task = TaskModel::create('Draft task');
        $activated = $task->activated(estimateTime: 90);

        $this->assertSame(TaskStatusEnum::Active, $activated->status);
        $this->assertSame(90, $activated->estimateTime);
        $this->assertSame(TaskStatusEnum::Initial, $task->status);
    }

    public function testActivateRejectsInvalidTransition(): void
    {
        $task = TaskModel::create('Draft task')->activated(30)->finished();

        $this->expectException(InvalidTaskStatusTransitionException::class);
        $task->activated(10);
    }

    public function testRejectsBlankName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TaskModel::create('   ');
    }

    public function testRejectsNameExceedingMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TaskModel::create(str_repeat('a', TaskModel::NAME_MAX_LENGTH + 1));
    }

    public function testRejectsNegativeEstimateTime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TaskModel::create('Draft task', estimateTime: -1);
    }

    public function testTaskUserModelCreate(): void
    {
        $user = TaskUserModel::create('user-1', TaskUserStatusEnum::Active);
        $this->assertSame('user-1', $user->userId);
        $this->assertSame(TaskUserStatusEnum::Active, $user->status);
    }
}
