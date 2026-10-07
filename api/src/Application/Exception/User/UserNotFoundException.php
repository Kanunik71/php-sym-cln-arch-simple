<?php

declare(strict_types=1);

namespace App\Application\Exception\User;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use DomainException;

final class UserNotFoundException extends DomainException implements CodedExceptionInterface
{
    public static function withEmail(string $email): self
    {
        return new self(sprintf('User with email "%s" not found.', $email));
    }

    public static function withId(string $id): self
    {
        return new self(sprintf('User with id "%s" not found.', $id));
    }

    public function getCodeId(): ErrorCodeEnum
    {
        return ErrorCodeEnum::UserNotFound;
    }
}
