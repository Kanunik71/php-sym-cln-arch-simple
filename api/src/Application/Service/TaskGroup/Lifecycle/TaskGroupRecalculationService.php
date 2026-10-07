<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Lifecycle;

use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;
use App\Application\Model\TaskGroup\TaskGroupMembershipFactsModel;
use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Shared\Utils\ArrayUtils;
use App\Shared\Utils\BatchUtils;

final readonly class TaskGroupRecalculationService
{
    public function __construct(
        private TaskGroupRepositoryInterface $taskGroupRepository,
        private TaskGroupLifecycleService $taskGroupLifecycleService,
        private int $batchSize = BatchUtils::DEFAULT_SIZE,
    ) {
    }

    public function recalculateByTaskId(string $taskId): void
    {
        $taskGroups = $this->taskGroupRepository->listByTaskId($taskId);

        BatchUtils::each(
            $taskGroups,
            fn (array $chunk) => $this->recalculateGroups($chunk),
            $this->batchSize,
        );
    }

    /**
     * @param list<string> $taskGroupIds
     */
    public function recalculateByGroupIds(array $taskGroupIds): void
    {
        BatchUtils::each(
            $taskGroupIds,
            function (array $chunk): void {
                $this->recalculateGroups($this->taskGroupRepository->listByIds($chunk));
            },
            $this->batchSize,
        );
    }

    /**
     * @param list<TaskGroupModel> $taskGroups
     */
    private function recalculateGroups(array $taskGroups): void
    {
        if ($taskGroups === []) {
            return;
        }

        $factsByGroupId = $this->taskGroupRepository->listMembershipFactsByIds(
            ArrayUtils::ids($taskGroups),
        );

        foreach ($taskGroups as $taskGroup) {
            $facts = $factsByGroupId[$taskGroup->id]
                ?? new TaskGroupMembershipFactsModel(taskCount: 0, hasNonTerminalTask: false);

            $previousStatus = $taskGroup->status;
            $updated = self::recalculated($taskGroup, $facts);
            if ($updated === null) {
                continue;
            }

            $saved = $this->taskGroupRepository->save($updated);
            $this->taskGroupLifecycleService->afterStatusChanged($saved, $previousStatus);
        }
    }

    /**
     * @return TaskGroupModel|null null when status unchanged
     */
    public static function recalculated(TaskGroupModel $taskGroup, TaskGroupMembershipFactsModel $facts): ?TaskGroupModel
    {
        $newStatus = self::statusFromMembership($facts->taskCount, $facts->hasNonTerminalTask);

        if ($taskGroup->status === $newStatus) {
            return null;
        }

        return $taskGroup->withStatus($newStatus);
    }

    public static function statusFromMembership(int $taskCount, bool $hasNonTerminalTask): TaskGroupStatusEnum
    {
        if ($taskCount === 0) {
            return TaskGroupStatusEnum::Initial;
        }

        if ($hasNonTerminalTask) {
            return TaskGroupStatusEnum::InProgress;
        }

        return TaskGroupStatusEnum::Completed;
    }
}
