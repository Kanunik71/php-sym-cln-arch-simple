<?php

declare(strict_types=1);

namespace App\Application\Exception\Auth;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use DomainException;

final class InvalidRefreshTokenException extends DomainException implements CodedExceptionInterface
{
    public static function create(): self
    {
        return new self('Invalid refresh token.');
    }

    public function getCodeId(): ErrorCodeEnum
    {
        return ErrorCodeEnum::InvalidRefreshToken;
    }
}
