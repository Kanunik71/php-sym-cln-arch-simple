<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Lifecycle;

use App\Application\Cache\TaskGroup\TaskGroupSummaryCache;
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
        private TaskGroupSummaryCache $summaryCache,
        private int $batchSize = BatchUtils::DEFAULT_SIZE,
    ) {
    }

    /**
     * @param callable(): void|null $onBatch вызывается перед каждым батчем,
     *                                       чтобы вызывающий мог продлить лок
     */
    public function recalculateByTaskId(string $taskId, ?callable $onBatch = null): void
    {
        $taskGroups = $this->taskGroupRepository->listByTaskId($taskId);

        BatchUtils::each(
            $taskGroups,
            function (array $chunk) use ($onBatch): void {
                if ($onBatch !== null) {
                    $onBatch();
                }

                $this->recalculateGroups($chunk);
            },
            $this->batchSize,
        );
    }

    /**
     * @param list<string>          $taskGroupIds
     * @param callable(): void|null $onBatch      вызывается перед каждым батчем,
     *                                            чтобы вызывающий мог продлить лок
     */
    public function recalculateByGroupIds(array $taskGroupIds, ?callable $onBatch = null): void
    {
        BatchUtils::each(
            $taskGroupIds,
            function (array $chunk) use ($onBatch): void {
                if ($onBatch !== null) {
                    $onBatch();
                }

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

            $updated = self::recalculated($taskGroup, $facts);
            if ($updated === null) {
                continue;
            }

            $this->taskGroupRepository->save($updated);
        }

        // Task status counters of the summary are derived from the tasks
        // themselves, so the cached summary is dropped for every processed
        // group — not only for the ones whose status has changed.
        $this->summaryCache->invalidate();
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
