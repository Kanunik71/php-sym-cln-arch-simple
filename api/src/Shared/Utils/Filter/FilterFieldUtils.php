<?php

declare(strict_types=1);

namespace App\Shared\Utils\Filter;

use App\Shared\Utils\EnumUtils;
use BackedEnum;
use InvalidArgumentException;

/**
 * Whitelist check for Search* filter field enums (*FilterFieldEnum).
 */
final class FilterFieldUtils
{
    /**
     * @template T of BackedEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return T
     */
    public static function isAllowed(string $name, string $enumClass): BackedEnum
    {
        /** @var T|null $case */
        $case = $enumClass::tryFrom($name);
        if ($case !== null) {
            return $case;
        }

        throw new InvalidArgumentException(sprintf(
            'Unknown filter field "%s". Allowed: %s.',
            $name,
            implode(', ', array_map(strval(...), EnumUtils::caseValues($enumClass))),
        ));
    }
}
