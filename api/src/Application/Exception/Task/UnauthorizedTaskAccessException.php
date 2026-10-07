<?php

declare(strict_types=1);

namespace App\Application\Exception\Task;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use DomainException;

final class UnauthorizedTaskAccessException extends DomainException implements CodedExceptionInterface
{
    public static function create(): self
    {
        return new self('You are not allowed to access this task.');
    }

    public function getCodeId(): ErrorCodeEnum
    {
        return ErrorCodeEnum::UnauthorizedTaskAccess;
    }
}
