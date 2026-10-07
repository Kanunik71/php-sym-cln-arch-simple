<?php

declare(strict_types=1);

namespace App\Application\Mapper\Task;

use App\Application\Model\Task\TaskModel;
use App\Application\Model\Task\TaskUserModel;
use App\Application\Model\Task\Read\TaskViewModel;
use App\Shared\Utils\ArrayUtils;
use App\Shared\Utils\DateUtils;

final class TaskViewModelMapper
{
    /**
     * @param list<TaskUserModel> $users
     */
    public static function fromModel(TaskModel $task, array $users = []): TaskViewModel
    {
        return new TaskViewModel(
            id: $task->id,
            name: $task->name,
            parentId: $task->parentId,
            estimateTime: $task->estimateTime,
            status: $task->status,
            createdAt: DateUtils::toAtom($task->createdAt),
            users: $users,
            cancelReason: $task->cancelReason,
            finishedDate: DateUtils::toAtomOrNull($task->finishedDate),
            cancellationDate: DateUtils::toAtomOrNull($task->cancellationDate),
        );
    }

    /**
     * @param list<array{task: TaskModel, users: list<TaskUserModel>}> $items
     *
     * @return list<TaskViewModel>
     */
    public static function fromModelList(array $items): array
    {
        return ArrayUtils::valuesMap(
            $items,
            static fn (array $item): TaskViewModel => self::fromModel($item['task'], $item['users']),
        );
    }
}
