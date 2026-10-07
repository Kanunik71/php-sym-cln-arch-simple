<?php

declare(strict_types=1);

namespace App\Shared\Utils\Asserts;

use InvalidArgumentException;

final class StringAssertUtils
{
    public static function notBlank(string $value, string $field = 'Value'): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException(sprintf('%s must not be empty.', $field));
        }

        return $trimmed;
    }

    public static function maxLength(string $value, int $maxLength, string $field = 'Value'): string
    {
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException(sprintf('%s must not exceed %d characters.', $field, $maxLength));
        }

        return $value;
    }

    public static function notBlankMaxLength(string $value, int $maxLength, string $field = 'Value'): string
    {
        return self::maxLength(self::notBlank($value, $field), $maxLength, $field);
    }

    public static function nullIfBlank(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    public static function maxLengthOrNull(?string $value, int $maxLength, string $field = 'Value'): ?string
    {
        return $value === null ? null : self::maxLength($value, $maxLength, $field);
    }
}
