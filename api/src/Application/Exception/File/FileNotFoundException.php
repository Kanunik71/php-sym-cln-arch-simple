<?php

declare(strict_types=1);

namespace App\Application\Exception\File;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use DomainException;

final class FileNotFoundException extends DomainException implements CodedExceptionInterface
{
    public static function withId(string $id): self
    {
        return new self(sprintf('File with id "%s" not found.', $id));
    }

    public function getCodeId(): ErrorCodeEnum
    {
        return ErrorCodeEnum::FileNotFound;
    }
}
