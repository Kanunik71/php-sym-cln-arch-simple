<?php

declare(strict_types=1);

namespace App\Application\Service\User\Query;

use App\Application\Enum\Common\SortDirectionEnum;
use App\Application\Enum\User\UserSortFieldEnum;
use App\Application\Mapper\User\UserViewModelMapper;
use App\Application\Model\Common\SortModel;
use App\Application\Model\User\Action\UserSearchModel;
use App\Application\Model\User\Read\UserPageModel;
use App\Application\Port\File\FileDownloadUrlProviderInterface;
use App\Application\Port\User\UserRepositoryInterface;
use App\Shared\Utils\Filter\SortFieldUtils;

final readonly class UserSearchQueryService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private FileDownloadUrlProviderInterface $fileDownloadUrlProvider,
    ) {
    }

    public function execute(UserSearchModel $model): UserPageModel
    {
        $sort = $model->sort ?? new SortModel(
            UserSortFieldEnum::CreatedAt->value,
            SortDirectionEnum::Desc,
        );

        SortFieldUtils::assertAllowed($sort->field, UserSortFieldEnum::values());

        $result = $this->userRepository->search(
            criteria: $model->criteria,
            pagination: $model->pagination,
            sort: $sort,
        );

        return new UserPageModel(
            items: UserViewModelMapper::fromModelList($result->items, $this->fileDownloadUrlProvider),
            total: $result->total,
            page: $model->pagination->page,
            perPage: $model->pagination->perPage,
        );
    }
}
