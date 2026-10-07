<?php

declare(strict_types=1);

namespace App\Application\Service\User\Query;

use App\Application\Model\Common\Query\SearchQueryMetaModel;
use App\Application\Enum\Common\SortDirectionEnum;
use App\Application\Enum\User\UserFilterFieldEnum;
use App\Application\Enum\User\UserSortFieldEnum;
use App\Application\Service\Common\SearchQueryMetaService;

final readonly class UserSearchMetaQueryService
{
    public function __construct(
        private SearchQueryMetaService $searchQueryMetaService,
    ) {
    }

    public function execute(): SearchQueryMetaModel
    {
        return $this->searchQueryMetaService->build(
            filterFields: UserFilterFieldEnum::cases(),
            sortFields: UserSortFieldEnum::values(),
            defaultSortField: UserSortFieldEnum::CreatedAt->value,
            defaultSortDirection: SortDirectionEnum::Desc,
        );
    }
}
