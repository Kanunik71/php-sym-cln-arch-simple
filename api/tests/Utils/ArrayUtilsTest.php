<?php

declare(strict_types=1);

namespace App\Tests\Utils;

use App\Application\Model\Common\IdentifiableInterface;
use App\Shared\Utils\ArrayUtils;
use PHPUnit\Framework\TestCase;

final class ArrayUtilsTest extends TestCase
{
    public function testIdsExtractsIdsInOrder(): void
    {
        $entities = [
            $this->identifiable('a'),
            $this->identifiable('b'),
            $this->identifiable('c'),
        ];

        $this->assertSame(['a', 'b', 'c'], ArrayUtils::ids($entities));
    }

    public function testIdsReturnsEmptyListForEmptyInput(): void
    {
        $this->assertSame([], ArrayUtils::ids([]));
    }

    public function testValuesMapMapsAndReindexes(): void
    {
        $items = [2 => 'a', 5 => 'b'];

        $this->assertSame(
            ['A', 'B'],
            ArrayUtils::valuesMap($items, static fn (string $item): string => strtoupper($item)),
        );
    }

    public function testValuesMapReturnsEmptyListForEmptyInput(): void
    {
        $this->assertSame([], ArrayUtils::valuesMap([], static fn (mixed $item): mixed => $item));
    }

    private function identifiable(string $id): IdentifiableInterface
    {
        return new class ($id) implements IdentifiableInterface {
            public function __construct(private readonly string $id)
            {
            }

            public function getId(): string
            {
                return $this->id;
            }
        };
    }
}
