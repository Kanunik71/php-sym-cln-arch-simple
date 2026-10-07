<?php

declare(strict_types=1);

namespace App\Application\Exception\Asset;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use DomainException;

final class UnauthorizedAssetAccessException extends DomainException implements CodedExceptionInterface
{
    public static function create(): self
    {
        return new self('You are not allowed to access this asset.');
    }

    public function getCodeId(): ErrorCodeEnum
    {
        return ErrorCodeEnum::UnauthorizedAssetAccess;
    }
}
