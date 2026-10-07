<?php

declare(strict_types=1);

namespace App\Application\ValueObject\Common;

use App\Shared\Utils\Asserts\StringAssertUtils;
use InvalidArgumentException;

final class Phone
{
    /** Persisted column max length (users.phone). */
    public const MAX_LENGTH = 20;

    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = preg_replace('/[\s\-()]/', '', StringAssertUtils::notBlank($value, 'Phone')) ?? '';
        $normalized = StringAssertUtils::maxLength($normalized, self::MAX_LENGTH, 'Phone');

        if (!preg_match('/^\+[1-9]\d{7,14}$/', $normalized)) {
            throw new InvalidArgumentException('Phone must be E.164 (e.g. +79001234567).');
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
