<?php

declare(strict_types=1);

namespace App\Shared\Utils;

use BackedEnum;

final class EnumUtils
{
    /**
     * @param list<BackedEnum> $enums
     *
     * @return list<string|int>
     */
    public static function values(array $enums): array
    {
        return array_map(
            static fn (BackedEnum $case): string|int => $case->value,
            $enums,
        );
    }

    /**
     * @template T of BackedEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return list<string|int>
     */
    public static function caseValues(string $enumClass): array
    {
        return self::values($enumClass::cases());
    }
}
