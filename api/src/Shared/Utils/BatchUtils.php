<?php

declare(strict_types=1);

namespace App\Shared\Utils;

use Generator;
use InvalidArgumentException;

final class BatchUtils
{
    public const DEFAULT_SIZE = 1000;

    /**
     * Lowest possible cursor — the starting point of a forward keyset walk.
     */
    public const FIRST_CURSOR = '00000000-0000-0000-0000-000000000000';

    /**
     * Highest possible cursor — the starting point of a backward keyset walk.
     */
    public const LAST_CURSOR = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

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

    /**
     * Walks a full table in batches, keeping only one batch in memory.
     *
     * The fetcher receives the cursor and returns the batch that follows it;
     * an empty batch ends the walk. Unlike each() over a pre-collected list,
     * the whole collection is never materialised, so memory stays O(batch) no
     * matter how many rows the table holds.
     *
     * The cursor is the boundary of the batch: the fetcher decides whether it
     * walks forward (rows after the cursor) or backward (rows before it).
     * A backward walk is the natural way to stream the newest rows first.
     *
     * @template T
     *
     * @param callable(string): list<T> $fetch    fetches the batch at the given cursor
     * @param callable(list<T>): string $cursorOf extracts the next cursor from the batch
     * @param callable(list<T>): void   $handle   processes one batch
     */
    public static function stream(
        callable $fetch,
        callable $cursorOf,
        callable $handle,
        string $startCursor = self::FIRST_CURSOR,
    ): void {
        $cursor = $startCursor;

        while (true) {
            $batch = $fetch($cursor);

            if ($batch === []) {
                return;
            }

            $handle($batch);

            $cursor = $cursorOf($batch);
        }
    }
}
