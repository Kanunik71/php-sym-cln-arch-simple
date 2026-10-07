<?php

declare(strict_types=1);

namespace App\Shared\Utils;

use Generator;
use InvalidArgumentException;

final class BatchUtils
{
    public const DEFAULT_SIZE = 1000;

    /**
     * @template T
     *
     * @param list<T> $items
     *
     * @return Generator<int, list<T>>
     */
    public static function chunk(array $items, int $size = self::DEFAULT_SIZE): Generator
    {
        if ($size < 1) {
            throw new InvalidArgumentException('Batch size must be at least 1.');
        }

        $count = count($items);

        for ($offset = 0; $offset < $count; $offset += $size) {
            /** @var list<T> $chunk */
            $chunk = array_slice($items, $offset, $size);

            yield $chunk;
        }
    }

    /**
     * @template T
     *
     * @param list<T>                 $items
     * @param callable(list<T>): void $callback
     */
    public static function each(array $items, callable $callback, int $size = self::DEFAULT_SIZE): void
    {
        foreach (self::chunk($items, $size) as $chunk) {
            $callback($chunk);
        }
    }
}
