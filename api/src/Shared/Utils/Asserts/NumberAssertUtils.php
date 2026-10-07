<?php

declare(strict_types=1);

namespace App\Shared\Utils\Asserts;

use InvalidArgumentException;

final class NumberAssertUtils
{
    public static function min(int $value, int $min, string $field = 'Value'): int
    {
        if ($value < $min) {
            throw new InvalidArgumentException(sprintf('%s must be %d or greater.', $field, $min));
        }

        return $value;
    }

    public static function minOrNull(?int $value, int $min, string $field = 'Value'): ?int
    {
        return $value === null ? null : self::min($value, $min, $field);
    }
}
