<?php

declare(strict_types=1);

namespace App\Application\Exception\User;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use DomainException;

final class UserAlreadyExistsException extends DomainException implements CodedExceptionInterface
{
    public static function withEmail(string $email): self
    {
        return new self(sprintf('User with email "%s" already exists.', $email));
    }

    public function getCodeId(): ErrorCodeEnum
    {
        return ErrorCodeEnum::UserAlreadyExists;
    }
}
