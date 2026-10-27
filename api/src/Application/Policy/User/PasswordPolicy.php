<?php

declare(strict_types=1);

namespace App\Application\Policy\User;

use InvalidArgumentException;

final readonly class PasswordPolicy
{
    public const MIN_LENGTH = 6;

    public const MAX_LENGTH = 72;

    public function assertValid(string $password): void
    {
        $length = strlen($password);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('Password must be between %d and %d bytes.', self::MIN_LENGTH, self::MAX_LENGTH));
        }
    }
}
