<?php

declare(strict_types=1);

namespace App\Shared\Utils;

final class CacheKeyUtils
{
    /**
     * Stable fingerprint for a set of string ids (order- and duplicate-insensitive).
     *
     * @param list<string> $ids
     */
    public static function fingerprintIds(array $ids): string
    {
        $uniqueIds = array_values(array_unique($ids));
        sort($uniqueIds);

        return hash('sha256', implode(',', $uniqueIds));
    }
}
