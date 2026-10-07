<?php

declare(strict_types=1);

namespace App\Shared\Utils;

use App\Application\Model\Common\IdentifiableInterface;

final class ArrayUtils
{
    /**
     * @template TIn
     * @template TOut
     *
     * @param array<TIn>           $items
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
}
