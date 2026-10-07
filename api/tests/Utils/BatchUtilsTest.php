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
}
