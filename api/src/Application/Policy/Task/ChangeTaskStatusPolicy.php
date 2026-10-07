<?php

declare(strict_types=1);

namespace App\Application\Policy\Task;

use App\Application\Model\Task\Action\TaskChangeStatusModel;
use App\Application\Enum\Task\TaskStatusEnum;
use InvalidArgumentException;

final readonly class ChangeTaskStatusPolicy
{
    /** @return TaskStatusEnum::Active|TaskStatusEnum::Canceled|TaskStatusEnum::Finished */
    public function resolveTargetStatus(TaskChangeStatusModel $model): TaskStatusEnum
    {
        if ($model->status === TaskStatusEnum::Initial) {
            throw new InvalidArgumentException('Invalid status. Allowed values: Active, Canceled, Finished.');
        }

        return $model->status;
    }

    public function requireEstimateTime(TaskChangeStatusModel $model): int
    {
        if ($model->estimateTime === null) {
            throw new InvalidArgumentException('Field "estimateTime" is required for Active status.');
        }

        return $model->estimateTime;
    }

    public function requireCancelReason(TaskChangeStatusModel $model): string
    {
        if ($model->cancelReason === null) {
            throw new InvalidArgumentException('Field "cancelReason" is required for Canceled status.');
        }

        return $model->cancelReason;
    }
}
