<?php

declare(strict_types=1);

namespace App\Application\Exception\File;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use DomainException;

final class InvalidFileStateException extends DomainException implements CodedExceptionInterface
{
    public static function cannotAttach(string $fileId, string $reason): self
    {
        return new self(sprintf('Cannot attach file "%s": %s.', $fileId, $reason));
    }

    public function getCodeId(): ErrorCodeEnum
    {
        return ErrorCodeEnum::InvalidFileState;
    }
}
