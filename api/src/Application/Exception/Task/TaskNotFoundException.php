<?php

declare(strict_types=1);

namespace App\Application\Exception\Task;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use DomainException;

final class TaskNotFoundException extends DomainException implements CodedExceptionInterface
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Task with id "%s" not found.', $id));
    }

    public function getCodeId(): ErrorCodeEnum
    {
        return ErrorCodeEnum::TaskNotFound;
    }
}
