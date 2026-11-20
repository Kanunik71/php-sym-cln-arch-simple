<?php

declare(strict_types=1);

namespace App\Application\Utils\Collection;

use App\Application\Model\Common\IdentifiableInterface;

final class ArrayUtils
{
    /**
     * @template TIn
     * @template TOut
     *
     * @param array<TIn>          $items
     * @param callable(TIn): TOut $mapper
     *
     * @return list<TOut>
     */
    public static function valuesMap(array $items, callable $mapper): array
    {
        return array_values(array_map($mapper, $items));
    }

    /**
     * @param array<IdentifiableInterface> $entities
     *
     * @return list<string>
     */
    public static function ids(array $entities): array
    {
        return self::valuesMap(
            $entities,
            static fn (IdentifiableInterface $entity): string => $entity->getId(),
        );
    }

    /**
     * Canonical list: no duplicates, in a deterministic order.
     *
     * The same set of values yields the same list — the message payload
     * does not depend on the order of the source data.
     *
     * @param list<string> $items
     *
     * @return list<string>
     */
    public static function uniqueSorted(array $items): array
    {
        $unique = array_values(array_unique($items));
        sort($unique, SORT_STRING);

        return $unique;
    }
}
