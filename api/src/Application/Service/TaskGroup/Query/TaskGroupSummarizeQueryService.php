<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Query;

use App\Application\Mapper\TaskGroup\TaskGroupSummaryModelMapper;
use App\Application\Model\TaskGroup\Read\TaskGroupSummaryModel;
use App\Application\Model\TaskGroup\Read\TaskStatusCountsModel;
use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Shared\Utils\ArrayUtils;
use App\Shared\Utils\BatchUtils;

final readonly class TaskGroupSummarizeQueryService
{
    public function __construct(
        private TaskGroupRepositoryInterface $taskGroupRepository,
    ) {
    }

    /** @return list<TaskGroupSummaryModel> */
    public function execute(): array
    {
        $taskGroups = $this->listGroups();
        usort(
            $taskGroups,
            static fn (TaskGroupModel $left, TaskGroupModel $right): int => $right->createdAt <=> $left->createdAt,
        );

        $summaries = [];
        foreach (BatchUtils::chunk($taskGroups) as $chunk) {
            $countsByGroupId = $this->taskGroupRepository->listTaskStatusCountsByIds(ArrayUtils::ids($chunk));
            foreach ($chunk as $taskGroup) {
                $counts = $countsByGroupId[$taskGroup->id] ?? TaskStatusCountsModel::empty();
                $summaries[] = TaskGroupSummaryModelMapper::fromModel($taskGroup, $counts);
            }
        }

        return $summaries;
    }

    /** @return list<TaskGroupModel> */
    private function listGroups(): array
    {
        $taskGroups = [];
        $offset = 0;

        do {
            $batch = $this->taskGroupRepository->listBatch($offset, BatchUtils::DEFAULT_SIZE);
            if ($batch === []) {
                break;
            }

            array_push($taskGroups, ...$batch);
            $offset += count($batch);
        } while (count($batch) === BatchUtils::DEFAULT_SIZE);

        return $taskGroups;
    }
}
