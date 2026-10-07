<?php

declare(strict_types=1);

namespace App\Tests\Application\Model\TaskGroup;

use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;
use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Shared\Utils\UidUtils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TaskGroupModelTest extends TestCase
{
    public function testCreateDefaultsToInitial(): void
    {
        $group = TaskGroupModel::create('Empty group', UidUtils::generateString());

        $this->assertSame(TaskGroupStatusEnum::Initial, $group->status);
    }

    public function testCreateKeepsOwner(): void
    {
        $ownerId = UidUtils::generateString();
        $group = TaskGroupModel::create('Owned group', $ownerId);

        $this->assertSame($ownerId, $group->ownerId);
    }

    public function testRejectsNameExceedingMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TaskGroupModel::create(str_repeat('a', TaskGroupModel::NAME_MAX_LENGTH + 1), UidUtils::generateString());
    }
}
