<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\TaskGroup\Lifecycle;

use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;
use App\Application\Model\TaskGroup\TaskGroupMembershipFactsModel;
use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Application\Service\TaskGroup\Lifecycle\TaskGroupRecalculationService;
use App\Shared\Utils\UidUtils;
use PHPUnit\Framework\TestCase;

final class TaskGroupRecalculationStatusTest extends TestCase
{
    public function testStatusFromMembership(): void
    {
        $this->assertSame(TaskGroupStatusEnum::Initial, TaskGroupRecalculationService::statusFromMembership(0, false));
        $this->assertSame(TaskGroupStatusEnum::InProgress, TaskGroupRecalculationService::statusFromMembership(1, true));
        $this->assertSame(TaskGroupStatusEnum::Completed, TaskGroupRecalculationService::statusFromMembership(1, false));
    }

    public function testRecalculatedReturnsNullWhenUnchanged(): void
    {
        $group = TaskGroupModel::create('Sprint', UidUtils::generateString(), TaskGroupStatusEnum::InProgress);

        $this->assertNull($this->recalculated($group, 1, true));

        $updated = $this->recalculated($group, 1, false);
        $this->assertNotNull($updated);
        $this->assertSame(TaskGroupStatusEnum::Completed, $updated->status);
        $this->assertNull($this->recalculated($updated, 1, false));
    }

    public function testRecalculatedToInitialWhenEmpty(): void
    {
        $group = TaskGroupModel::create('Sprint', UidUtils::generateString(), TaskGroupStatusEnum::InProgress);
        $updated = $this->recalculated($group, 0, false);

        $this->assertNotNull($updated);
        $this->assertSame(TaskGroupStatusEnum::Initial, $updated->status);
    }

    private function recalculated(TaskGroupModel $group, int $taskCount, bool $hasNonTerminalTask): ?TaskGroupModel
    {
        return TaskGroupRecalculationService::recalculated(
            $group,
            new TaskGroupMembershipFactsModel(taskCount: $taskCount, hasNonTerminalTask: $hasNonTerminalTask),
        );
    }
}
