<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Query;

use App\Application\Cache\TaskGroup\TaskGroupSummaryCache;
use App\Application\Mapper\TaskGroup\TaskGroupSummaryModelMapper;
use App\Application\Model\TaskGroup\Read\TaskGroupSummaryModel;
use App\Application\Model\TaskGroup\Read\TaskStatusCountsModel;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Application\Utils\Collection\ArrayUtils;
use App\Application\Utils\Collection\BatchUtils;

final readonly class TaskGroupSummarizeQueryService
{
    public function __construct(
        private TaskGroupRepositoryInterface $taskGroupRepository,
        private TaskGroupSummaryCache $summaryCache,
        private int $batchSize = BatchUtils::DEFAULT_SIZE,
    ) {
    }

    /**
     * Summarises every task group, newest first.
     *
     * The summary is served from the cache: on a miss the whole table is
     * walked with a keyset cursor, so the cost stays linear in the number of
     * rows; the groups themselves are never materialised all at once — only
     * one batch is held at a time.
     *
     * @return list<TaskGroupSummaryModel>
     */
    public function execute(): array
    {
        return $this->summaryCache->get(fn (): array => $this->summarize());
    }

    /**
     * @return list<TaskGroupSummaryModel>
     */
    private function summarize(): array
    {
        $summaries = [];

        BatchUtils::stream(
            fetch: fn (string $beforeId): array => $this->taskGroupRepository->listBatchBefore(
                $beforeId,
                $this->batchSize,
            ),
            cursorOf: static fn (array $batch): string => $batch[count($batch) - 1]->getId(),
            handle: function (array $batch) use (&$summaries): void {
                $countsByGroupId = $this->taskGroupRepository->listTaskStatusCountsByIds(ArrayUtils::ids($batch));

                foreach ($batch as $taskGroup) {
                    $counts = $countsByGroupId[$taskGroup->id] ?? TaskStatusCountsModel::empty();
                    $summaries[] = TaskGroupSummaryModelMapper::fromModel($taskGroup, $counts);
                }
            },
            startCursor: BatchUtils::LAST_CURSOR,
        );

        return $summaries;
    }
}
