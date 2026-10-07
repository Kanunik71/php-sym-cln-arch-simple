<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Query;

use App\Application\Mapper\TaskGroup\TaskGroupSummaryModelMapper;
use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Application\Model\TaskGroup\Read\TaskGroupSummaryModel;
use App\Application\Model\TaskGroup\Read\TaskStatusCountsModel;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Shared\Utils\ArrayUtils;

final readonly class TaskGroupSummarizeQueryService
{
    public function __construct(
        private TaskGroupRepositoryInterface $taskGroupRepository,
    ) {
    }

    /** @return list<TaskGroupSummaryModel> */
    public function execute(): array
    {
        $taskGroups = $this->taskGroupRepository->listAll();

        $groupIds = ArrayUtils::ids($taskGroups);

        $countsByGroupId = $this->taskGroupRepository->listTaskStatusCountsByIds($groupIds);

        return ArrayUtils::valuesMap(
            $taskGroups,
            static function (TaskGroupModel $taskGroup) use ($countsByGroupId): TaskGroupSummaryModel {
                $counts = $countsByGroupId[$taskGroup->id] ?? TaskStatusCountsModel::empty();

                return TaskGroupSummaryModelMapper::fromModel($taskGroup, $counts);
            },
        );
    }
}
