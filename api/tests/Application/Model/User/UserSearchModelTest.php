<?php

declare(strict_types=1);

namespace App\Tests\Application\Model\User;

use App\Application\Enum\Common\FilterOperatorEnum;
use App\Application\Enum\User\UserFilterFieldEnum;
use App\Application\Model\Common\Filter\StringFilterModel;
use App\Application\Model\User\Action\UserSearchModel;
use App\Shared\Utils\UidUtils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UserSearchModelTest extends TestCase
{
    public function testFromArrayParsesPaginationFiltersAndSort(): void
    {
        $model = UserSearchModel::fromArray([
            'page' => '2',
            'perPage' => '10',
            'sort' => '-email',
            'filter' => [
                'city' => ['eq' => 'Moscow'],
                'email' => ['like' => '@example.com'],
            ],
        ]);

        self::assertSame(2, $model->pagination->page);
        self::assertSame(10, $model->pagination->perPage);
        self::assertSame(10, $model->pagination->offset());

        self::assertSame('email', $model->sort?->field);
        self::assertCount(2, $model->criteria);

        self::assertSame(UserFilterFieldEnum::City, $model->criteria[0]->field);
        self::assertInstanceOf(StringFilterModel::class, $model->criteria[0]->filter);
        self::assertSame(FilterOperatorEnum::Eq, $model->criteria[0]->filter->operator);
        self::assertSame('Moscow', $model->criteria[0]->filter->value);

        self::assertSame(UserFilterFieldEnum::Email, $model->criteria[1]->field);
        self::assertInstanceOf(StringFilterModel::class, $model->criteria[1]->filter);
        self::assertSame(FilterOperatorEnum::Like, $model->criteria[1]->filter->operator);
        self::assertSame('@example.com', $model->criteria[1]->filter->value);
    }

    public function testFromArrayParsesTaskIdFilter(): void
    {
        $taskId = UidUtils::generateString();

        $model = UserSearchModel::fromArray([
            'filter' => [
                'taskId' => ['eq' => $taskId],
            ],
        ]);

        self::assertCount(1, $model->criteria);
        self::assertSame(UserFilterFieldEnum::TaskId, $model->criteria[0]->field);
        self::assertInstanceOf(StringFilterModel::class, $model->criteria[0]->filter);
        self::assertSame(FilterOperatorEnum::Eq, $model->criteria[0]->filter->operator);
        self::assertSame($taskId, $model->criteria[0]->filter->value);
    }

    public function testFromArrayRejectsUnknownFilterField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown filter field "password"');

        UserSearchModel::fromArray([
            'filter' => [
                'password' => ['eq' => 'secret'],
            ],
        ]);
    }

    public function testFromArrayLeavesSortNullWhenAbsent(): void
    {
        $model = UserSearchModel::fromArray([
            'page' => '1',
            'perPage' => '10',
        ]);

        self::assertNull($model->sort);
    }
}
