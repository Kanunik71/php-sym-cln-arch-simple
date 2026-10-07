<?php

declare(strict_types=1);

namespace App\Application\Service\User\Query;

use App\Application\Mapper\User\UserViewModelMapper;
use App\Application\Model\User\Read\UserViewModel;
use App\Application\Port\File\FileDownloadUrlProviderInterface;
use App\Application\Port\User\UserRepositoryInterface;

final readonly class UserQueryService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private FileDownloadUrlProviderInterface $fileDownloadUrlProvider,
    ) {
    }

    public function execute(string $userId): UserViewModel
    {
        $user = $this->userRepository->findOrFail($userId);

        return UserViewModelMapper::fromModel($user, $this->fileDownloadUrlProvider);
    }
}
