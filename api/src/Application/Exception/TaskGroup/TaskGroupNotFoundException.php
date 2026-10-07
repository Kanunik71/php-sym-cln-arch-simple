<?php

declare(strict_types=1);

namespace App\Application\Exception\TaskGroup;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use DomainException;

final class TaskGroupNotFoundException extends DomainException implements CodedExceptionInterface
{
    public static function withId(string $id): self
    {
        return new self(sprintf('TaskGroup with id "%s" not found.', $id));
    }

    public function getCodeId(): ErrorCodeEnum
    {
        return ErrorCodeEnum::TaskGroupNotFound;
    }
}
