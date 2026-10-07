<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\User\Query;

use App\Application\Model\Common\Query\SearchQueryMetaModel;
use App\Application\Enum\Common\FilterOperatorEnum;
use App\Application\Enum\Common\SortDirectionEnum;
use App\Application\Enum\User\UserFilterFieldEnum;
use App\Application\Enum\User\UserSortFieldEnum;
use App\Application\Model\Common\PaginationModel;
use App\Application\Service\User\Query\UserSearchMetaQueryService;
use App\Application\Service\Common\SearchQueryMetaService;
use PHPUnit\Framework\TestCase;

final class UserSearchMetaQueryServiceTest extends TestCase
{
    public function testReturnsSearchUsersQueryMeta(): void
    {
        $searchUsersMeta = new UserSearchMetaQueryService(new SearchQueryMetaService());

        $meta = $searchUsersMeta->execute();

        self::assertInstanceOf(SearchQueryMetaModel::class, $meta);
        self::assertCount(count(UserFilterFieldEnum::cases()), $meta->filters);
        self::assertSame(UserSortFieldEnum::values(), $meta->sortFields);
        self::assertSame(
            [SortDirectionEnum::Asc->value, SortDirectionEnum::Desc->value],
            $meta->sortDirections,
        );
        self::assertSame(UserSortFieldEnum::CreatedAt->value, $meta->defaultSort->field);
        self::assertSame(SortDirectionEnum::Desc->value, $meta->defaultSort->direction);
        self::assertSame(PaginationModel::DEFAULT_PAGE, $meta->pagination->defaultPage);
        self::assertSame(PaginationModel::DEFAULT_PER_PAGE, $meta->pagination->defaultPerPage);
        self::assertSame(PaginationModel::MAX_PER_PAGE, $meta->pagination->maxPerPage);

        $taskIdFilter = null;
        foreach ($meta->filters as $filter) {
            if ($filter->field === UserFilterFieldEnum::TaskId->value) {
                $taskIdFilter = $filter;
                break;
            }
        }

        self::assertNotNull($taskIdFilter);
        self::assertSame(
            [FilterOperatorEnum::Eq->value, FilterOperatorEnum::In->value],
            $taskIdFilter->operators,
        );
    }
}
