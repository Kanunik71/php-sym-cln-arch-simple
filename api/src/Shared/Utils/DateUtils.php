<?php

declare(strict_types=1);

namespace App\Shared\Utils;

use DateTimeInterface;

final class DateUtils
{
    public static function toAtom(DateTimeInterface $date): string
    {
        return $date->format(DateTimeInterface::ATOM);
    }

    public static function toAtomOrNull(?DateTimeInterface $date): ?string
    {
        return $date !== null ? self::toAtom($date) : null;
    }
}
