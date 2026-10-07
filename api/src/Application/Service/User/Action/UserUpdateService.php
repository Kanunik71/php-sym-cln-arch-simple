<?php

declare(strict_types=1);

namespace App\Application\Service\User\Action;

use App\Application\Mapper\User\UserViewModelMapper;
use App\Application\Model\User\Action\UserUpdateModel;
use App\Application\Model\User\Read\UserViewModel;
use App\Application\Policy\User\UpdateUserPolicy;
use App\Application\Port\File\FileDownloadUrlProviderInterface;
use App\Application\Port\PasswordHasherInterface;
use App\Application\Port\User\UserRepositoryInterface;
use App\Application\Service\File\Lifecycle\FileLifecycleService;
use App\Application\Service\User\Lifecycle\UserLifecycleService;
use App\Application\ValueObject\Common\Email;

final readonly class UserUpdateService
{
    public function __construct(
        private UpdateUserPolicy $updateUserPolicy,
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private FileLifecycleService $fileLifecycleService,
        private FileDownloadUrlProviderInterface $fileDownloadUrlProvider,
        private UserLifecycleService $userLifecycleService,
    ) {
    }

    public function execute(UserUpdateModel $model, string $userId, string $currentUserId): UserViewModel
    {
        $user = $this->userRepository->findOrFail($userId);

        $this->updateUserPolicy->assertCanUpdate($userId, $currentUserId);

        $email = new Email($model->email);

        $this->updateUserPolicy->assertEmailAvailableIo($email, $user);

        $passwordHash = $model->password !== null
            ? $this->passwordHasher->hash($model->password)
            : $user->passwordHash;

        $updatedUser = $this->userRepository->save($user->withProfile(
            fname: $model->fname,
            lname: $model->lname,
            email: $email,
            passwordHash: $passwordHash,
            city: $model->location?->city,
            avatarFileId: $model->avatarFileId,
            phone: $model->phone,
        ));

        $this->fileLifecycleService->syncFileIds(
            $user->getAllFileIds(),
            $updatedUser->getAllFileIds(),
            $userId,
        );

        $this->userLifecycleService->afterUserPersisted($updatedUser, created: false);

        return UserViewModelMapper::fromModel($updatedUser, $this->fileDownloadUrlProvider);
    }
}
