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
     * Канонический список: без дублей, в детерминированном порядке.
     *
     * Одинаковый набор значений даёт одинаковый список — payload сообщения
     * не зависит от порядка исходных данных.
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
