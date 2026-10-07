<?php

declare(strict_types=1);

namespace App\Tests\Application\Model\User;

use App\Application\Enum\Common\FilterOperatorEnum;
use App\Application\Enum\User\UserFilterFieldEnum;
use App\Application\Model\Common\Filter\StringFilterModel;
use App\Application\Model\User\UserFilterCriterionModel;
use PHPUnit\Framework\TestCase;

final class UserFilterCriterionModelTest extends TestCase
{
    public function testFromArrayListAndToArrayListRoundTrip(): void
    {
        $data = [
            'filter' => [
                'city' => ['eq' => 'Moscow'],
                'email' => ['like' => '@example.com'],
            ],
        ];

        $criteria = UserFilterCriterionModel::fromArrayList($data);

        self::assertCount(2, $criteria);
        self::assertSame(UserFilterFieldEnum::City, $criteria[0]->field);
        self::assertInstanceOf(StringFilterModel::class, $criteria[0]->filter);
        self::assertSame(FilterOperatorEnum::Eq, $criteria[0]->filter->operator);
        self::assertSame('Moscow', $criteria[0]->filter->value);

        self::assertSame($data, UserFilterCriterionModel::toArrayList($criteria));
    }

    public function testToArrayListEmpty(): void
    {
        self::assertSame([], UserFilterCriterionModel::toArrayList([]));
    }
}
