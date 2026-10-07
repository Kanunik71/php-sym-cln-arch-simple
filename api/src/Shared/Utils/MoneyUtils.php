<?php

declare(strict_types=1);

namespace App\Shared\Utils;

use InvalidArgumentException;

final class MoneyUtils
{
    /** Matches ORM decimal(PRECISION, SCALE) for asset price. */
    public const PRECISION = 10;

    public const SCALE = 2;

    /** Max value for decimal(10, 2): 8 digits before the decimal point. */
    public const MAX_AMOUNT = 99_999_999.99;

    public static function normalize(float $amount): float
    {
        if (!is_finite($amount) || $amount < 0) {
            throw new InvalidArgumentException('Price must be a non-negative number with up to 2 decimal places.');
        }

        $normalized = round($amount, self::SCALE);

        if ($normalized > self::MAX_AMOUNT) {
            throw new InvalidArgumentException(sprintf(
                'Price must not exceed %s.',
                number_format(self::MAX_AMOUNT, self::SCALE, '.', ''),
            ));
        }

        return $normalized;
    }

    public static function toString(float $amount): string
    {
        return number_format(self::normalize($amount), self::SCALE, '.', '');
    }

    public static function fromString(string $amount): float
    {
        return self::normalize((float) $amount);
    }
}
