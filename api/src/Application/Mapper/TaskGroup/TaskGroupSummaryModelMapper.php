<?php

declare(strict_types=1);

namespace App\Application\Mapper\TaskGroup;

use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Application\Model\TaskGroup\Read\TaskGroupSummaryModel;
use App\Application\Model\TaskGroup\Read\TaskStatusCountsModel;

final class TaskGroupSummaryModelMapper
{
    public static function fromModel(
        TaskGroupModel $taskGroup,
        TaskStatusCountsModel $taskStatusCounts,
    ): TaskGroupSummaryModel {
        return new TaskGroupSummaryModel(
            id: $taskGroup->id,
            name: $taskGroup->name,
            taskStatusCounts: $taskStatusCounts,
        );
    }
}
