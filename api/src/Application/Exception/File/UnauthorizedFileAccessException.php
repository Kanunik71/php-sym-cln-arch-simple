<?php

declare(strict_types=1);

namespace App\Application\Exception\File;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use DomainException;

final class UnauthorizedFileAccessException extends DomainException implements CodedExceptionInterface
{
    public static function forUser(string $fileId, string $userId): self
    {
        return new self(sprintf('User "%s" cannot access file "%s".', $userId, $fileId));
    }

    public static function invalidSignature(string $fileId): self
    {
        return new self(sprintf('Download signature for file "%s" is invalid.', $fileId));
    }

    public function getCodeId(): ErrorCodeEnum
    {
        return ErrorCodeEnum::UnauthorizedFileAccess;
    }
}
