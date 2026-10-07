<?php

declare(strict_types=1);

namespace App\Application\Exception\User;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use DomainException;

final class InvalidCredentialsException extends DomainException implements CodedExceptionInterface
{
    public static function create(): self
    {
        return new self('Invalid credentials.');
    }

    public function getCodeId(): ErrorCodeEnum
    {
        return ErrorCodeEnum::InvalidCredentials;
    }
}
