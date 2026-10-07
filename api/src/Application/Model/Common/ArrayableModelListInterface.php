<?php

declare(strict_types=1);

namespace App\Application\Model\Common;

/**
 * Wire bag → list of Models (e.g. Search* `filter` → list<*FilterCriterionModel>).
 */
interface ArrayableModelListInterface
{
    /**
     * @param array<string, mixed> $data
     *
     * @return list<static>
     */
    public static function fromArrayList(array $data): array;

    /**
     * @param list<static> $items
     *
     * @return array<string, mixed>
     */
    public static function toArrayList(array $items): array;
}
