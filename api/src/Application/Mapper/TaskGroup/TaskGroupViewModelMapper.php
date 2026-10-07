<?php

declare(strict_types=1);

namespace App\Application\Mapper\TaskGroup;

use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Application\Model\TaskGroup\Read\TaskGroupViewModel;
use App\Shared\Utils\ArrayUtils;
use App\Shared\Utils\DateUtils;

final class TaskGroupViewModelMapper
{
    public static function fromModel(TaskGroupModel $taskGroup): TaskGroupViewModel
    {
        return new TaskGroupViewModel(
            id: $taskGroup->id,
            ownerId: $taskGroup->ownerId,
            name: $taskGroup->name,
            status: $taskGroup->status,
            createdAt: DateUtils::toAtom($taskGroup->createdAt),
        );
    }

    /**
     * @param TaskGroupModel[] $taskGroups
     *
     * @return list<TaskGroupViewModel>
     */
    public static function fromModelList(array $taskGroups): array
    {
        return ArrayUtils::valuesMap(
            $taskGroups,
            static fn (TaskGroupModel $taskGroup): TaskGroupViewModel => self::fromModel($taskGroup),
        );
    }
}
