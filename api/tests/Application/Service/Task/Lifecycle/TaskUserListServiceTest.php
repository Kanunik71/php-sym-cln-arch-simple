<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Task\Lifecycle;

use App\Application\Enum\Task\TaskUserStatusEnum;
use App\Application\Service\Task\Lifecycle\TaskUserListService;
use PHPUnit\Framework\TestCase;

final class TaskUserListServiceTest extends TestCase
{
    public function testBelongsAndAssign(): void
    {
        $users = [];
        $this->assertTrue(TaskUserListService::belongsTo($users, 'anyone'));

        $users = TaskUserListService::assign($users, 'user-123', TaskUserStatusEnum::Initial);
        $this->assertTrue(TaskUserListService::belongsTo($users, 'user-123'));
        $this->assertFalse(TaskUserListService::belongsTo($users, 'other-user'));

        $users = TaskUserListService::unassign($users, 'user-123');
        $this->assertTrue(TaskUserListService::belongsTo($users, 'anyone'));
        $this->assertSame([], TaskUserListService::ids($users));
    }
}
