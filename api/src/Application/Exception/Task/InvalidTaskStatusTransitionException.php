<?php

declare(strict_types=1);

namespace App\Application\Exception\Task;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use App\Application\Enum\Task\TaskStatusEnum;
use DomainException;

final class InvalidTaskStatusTransitionException extends DomainException implements CodedExceptionInterface
{
    public static function fromTo(TaskStatusEnum $from, TaskStatusEnum $to): self
    {
        return new self(sprintf(
            'Cannot transition task status from "%s" to "%s".',
            $from->value,
            $to->value,
        ));
    }

    public function getCodeId(): ErrorCodeEnum
    {
        return ErrorCodeEnum::InvalidTaskStatusTransition;
    }
}
