<?php

declare(strict_types=1);

namespace App\Application\Service\User\Query;

use App\Application\Mapper\User\UserViewModelMapper;
use App\Application\Model\User\Read\UserViewModel;
use App\Application\Port\File\FileDownloadUrlProviderInterface;
use App\Application\Port\User\UserRepositoryInterface;

final readonly class UserListQueryService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private FileDownloadUrlProviderInterface $fileDownloadUrlProvider,
    ) {
    }

    /** @return list<UserViewModel> */
    public function execute(): array
    {
        return UserViewModelMapper::fromModelList(
            $this->userRepository->listAll(),
            $this->fileDownloadUrlProvider,
        );
    }
}
