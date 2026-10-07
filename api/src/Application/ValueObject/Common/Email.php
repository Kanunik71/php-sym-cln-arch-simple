<?php

declare(strict_types=1);

namespace App\Application\ValueObject\Common;

use App\Shared\Utils\Asserts\StringAssertUtils;
use InvalidArgumentException;

final class Email
{
    /** Persisted column max length (users.email). */
    public const MAX_LENGTH = 255;

    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(StringAssertUtils::notBlankMaxLength($value, self::MAX_LENGTH, 'Email'));

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Email is invalid.');
        }

        $this->value = $normalized;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
