<?php

declare(strict_types=1);

namespace App\Application\Utils\Wire;

use InvalidArgumentException;

/**
 * Whitelist check for Search* sort fields (*SortFieldEnum::values()).
 */
final class SortFieldUtils
{
    /**
     * @param list<string> $allowedFields
     */
    public static function assertAllowed(string $field, array $allowedFields): void
    {
        if (in_array($field, $allowedFields, true)) {
            return;
        }

        throw new InvalidArgumentException(sprintf('Field "sort" must be one of: %s (prefix "-" for desc).', implode(', ', $allowedFields)));
    }
}
