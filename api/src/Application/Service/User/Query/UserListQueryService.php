<?php

declare(strict_types=1);

namespace App\Application\Service\User\Query;

use App\Application\Model\Common\PaginationModel;
use App\Application\Model\User\Action\UserSearchModel;
use App\Application\Model\User\Read\UserPageModel;

final readonly class UserListQueryService
{
    public function __construct(
        private UserSearchQueryService $searchUsers,
    ) {
    }

    public function execute(PaginationModel $pagination): UserPageModel
    {
        return $this->searchUsers->execute(new UserSearchModel(
            pagination: $pagination,
            criteria: [],
        ));
    }
}
