<?php

declare(strict_types=1);

namespace App\Application\Mapper\Task;

use App\Application\Model\Task\Read\TaskLightModel;
use App\Application\Model\Task\TaskModel;
use App\Shared\Utils\ArrayUtils;
use App\Shared\Utils\DateUtils;

final class TaskLightModelMapper
{
    public static function fromModel(TaskModel $task): TaskLightModel
    {
        return new TaskLightModel(
            id: $task->id,
            name: $task->name,
            parentId: $task->parentId,
            estimateTime: $task->estimateTime,
            status: $task->status,
            createdAt: DateUtils::toAtom($task->createdAt),
        );
    }

    /**
     * @param array<TaskModel> $tasks
     *
     * @return list<TaskLightModel>
     */
    public static function fromModelList(array $tasks): array
    {
        return ArrayUtils::valuesMap($tasks, self::fromModel(...));
    }
}
