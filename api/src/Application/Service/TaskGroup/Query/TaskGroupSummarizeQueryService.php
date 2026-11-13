<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Query;

use App\Application\Mapper\TaskGroup\TaskGroupSummaryModelMapper;
use App\Application\Model\TaskGroup\Read\TaskGroupSummaryModel;
use App\Application\Model\TaskGroup\Read\TaskStatusCountsModel;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Shared\Utils\ArrayUtils;
use App\Shared\Utils\BatchUtils;

final readonly class TaskGroupSummarizeQueryService
{
    public function __construct(
        private TaskGroupRepositoryInterface $taskGroupRepository,
        private int $batchSize = BatchUtils::DEFAULT_SIZE,
    ) {
    }

    /**
     * Summarises every task group, newest first.
     *
     * The whole table is walked with a keyset cursor, so the cost stays
     * linear in the number of rows; the groups themselves are never
     * materialised all at once — only one batch is held at a time.
     *
     * @return list<TaskGroupSummaryModel>
     */
    public function execute(): array
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
