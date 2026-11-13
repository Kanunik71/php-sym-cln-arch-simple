<?php

declare(strict_types=1);

namespace App\Tests\Utils;

use App\Shared\Utils\BatchUtils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BatchUtilsTest extends TestCase
{
    public function testChunkSplitsItemsBySize(): void
    {
        $chunks = iterator_to_array(BatchUtils::chunk([1, 2, 3, 4, 5], 2), false);

        $this->assertSame([[1, 2], [3, 4], [5]], $chunks);
    }

    public function testChunkReturnsEmptyGeneratorForEmptyInput(): void
    {
        $chunks = iterator_to_array(BatchUtils::chunk([], 10), false);

        $this->assertSame([], $chunks);
    }

    public function testChunkRejectsNonPositiveSize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        iterator_to_array(BatchUtils::chunk([1], 0), false);
    }

    public function testEachInvokesCallbackPerChunk(): void
    {
        $seen = [];

        BatchUtils::each(
            ['a', 'b', 'c', 'd'],
            static function (array $chunk) use (&$seen): void {
                $seen[] = $chunk;
            },
            3,
        );

        $this->assertSame([['a', 'b', 'c'], ['d']], $seen);
    }

    public function testEachDoesNothingForEmptyInput(): void
    {
        $called = false;

        BatchUtils::each([], static function () use (&$called): void {
            $called = true;
        });

        $this->assertFalse($called);
    }

    public function testStreamWalksBatchesUntilEmptyOne(): void
    {
        // Курсор = смещение следующей выборки; строки нумеруются с 1.
        $rows = range(1, 25);
        $seen = [];
        $cursors = [];

        BatchUtils::stream(
            fetch: function (string $cursor) use ($rows, &$cursors): array {
                $cursors[] = $cursor;

                $offset = (int) $cursor;

                return array_slice($rows, $offset, 10);
            },
            cursorOf: static fn (array $batch): string => (string) ($batch[0] + 9),
            handle: static function (array $batch) use (&$seen): void {
                $seen = array_merge($seen, $batch);
            },
        );

        $this->assertSame(range(1, 25), $seen);
        // курсоры: стартовый, затем по одному на каждый полный батч и на последний неполный
        $this->assertSame([BatchUtils::FIRST_CURSOR, '10', '20', '30'], $cursors);
    }

    public function testStreamStopsOnEmptyBatch(): void
    {
        $calls = 0;

        BatchUtils::stream(
            fetch: static function () use (&$calls): array {
                ++$calls;

                return [];
            },
            cursorOf: static fn (array $batch): string => 'next',
            handle: static function (array $batch): void {
            },
        );

        $this->assertSame(1, $calls);
    }

    public function testStreamStartsFromGivenCursor(): void
    {
        $cursors = [];

        BatchUtils::stream(
            fetch: function (string $cursor) use (&$cursors): array {
                $cursors[] = $cursor;

                return [];
            },
            cursorOf: static fn (array $batch): string => 'next',
            handle: static function (array $batch): void {
            },
            startCursor: BatchUtils::LAST_CURSOR,
        );

        $this->assertSame([BatchUtils::LAST_CURSOR], $cursors);
    }
}
