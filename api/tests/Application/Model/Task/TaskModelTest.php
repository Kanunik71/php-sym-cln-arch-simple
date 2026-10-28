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
    private const OWNER_ID = '11111111-1111-1111-1111-111111111111';

    public function testCreateSetsInitialStatus(): void
    {
        $task = TaskModel::create('Parent task', self::OWNER_ID);
        $child = TaskModel::create('Child task', self::OWNER_ID, parentId: $task->id);

        $this->assertSame(TaskStatusEnum::Initial, $task->status);
        $this->assertSame(self::OWNER_ID, $task->ownerId);
        $this->assertSame($task->id, $child->parentId);
    }

    public function testActivatedTransitionsStatus(): void
    {
        $task = TaskModel::create('Draft task', self::OWNER_ID);
        $activated = $task->activated(estimateTime: 90);

        $this->assertSame(TaskStatusEnum::Active, $activated->status);
        $this->assertSame(90, $activated->estimateTime);
        $this->assertSame(self::OWNER_ID, $activated->ownerId);
        $this->assertSame(TaskStatusEnum::Initial, $task->status);
    }

    public function testActivateRejectsInvalidTransition(): void
    {
        $task = TaskModel::create('Draft task', self::OWNER_ID)->activated(30)->finished();

        $this->expectException(InvalidTaskStatusTransitionException::class);
        $task->activated(10);
    }

    public function testRejectsBlankName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TaskModel::create('   ', self::OWNER_ID);
    }

    public function testRejectsNameExceedingMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TaskModel::create(str_repeat('a', TaskModel::NAME_MAX_LENGTH + 1), self::OWNER_ID);
    }

    public function testRejectsNegativeEstimateTime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TaskModel::create('Draft task', self::OWNER_ID, estimateTime: -1);
    }

    public function testTaskUserModelCreate(): void
    {
        $user = TaskUserModel::create('user-1', TaskUserStatusEnum::Active);
        $this->assertSame('user-1', $user->userId);
        $this->assertSame(TaskUserStatusEnum::Active, $user->status);
    }
}
