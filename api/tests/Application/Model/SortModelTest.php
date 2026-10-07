<?php

declare(strict_types=1);

namespace App\Tests\Application\Model;

use App\Application\Enum\Common\SortDirectionEnum;
use App\Application\Model\Common\SortModel;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SortModelTest extends TestCase
{
    public function testFromArrayAndToArrayRoundTripDesc(): void
    {
        $sort = SortModel::fromArray(['sort' => '-email']);

        self::assertSame('email', $sort->field);
        self::assertSame(SortDirectionEnum::Desc, $sort->direction);
        self::assertSame(['sort' => '-email'], $sort->toArray());
    }

    public function testFromArrayAndToArrayRoundTripAsc(): void
    {
        $sort = SortModel::fromArray(['sort' => 'createdAt']);

        self::assertSame('createdAt', $sort->field);
        self::assertSame(SortDirectionEnum::Asc, $sort->direction);
        self::assertSame(['sort' => 'createdAt'], $sort->toArray());
    }

    public function testFromArrayRequiresSort(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SortModel::fromArray([]);
    }
}
