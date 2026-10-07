<?php

declare(strict_types=1);

namespace App\Application\Service\User\Action;

use App\Application\Mapper\User\UserRegisterModelMapper;
use App\Application\Model\User\Action\UserRegisterModel;
use App\Application\Model\User\Read\UserRegisterResponseModel;
use App\Application\Model\User\UserModel;
use App\Application\Policy\User\RegisterUserPolicy;
use App\Application\Port\Notification\NotificationPreferenceRepositoryInterface;
use App\Application\Port\PasswordHasherInterface;
use App\Application\Port\TransactionManagerInterface;
use App\Application\Port\User\UserRepositoryInterface;
use App\Application\Service\User\Lifecycle\UserLifecycleService;
use App\Application\ValueObject\Common\Email;
use App\Application\Model\Notification\NotificationPreferenceModel;

final readonly class UserRegisterService
{
    public function __construct(
        private RegisterUserPolicy $registerUserPolicy,
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private NotificationPreferenceRepositoryInterface $preferenceRepository,
        private TransactionManagerInterface $transactionManager,
        private UserLifecycleService $userLifecycleService,
    ) {
    }

    public function execute(UserRegisterModel $model): UserRegisterResponseModel
    {
        $email = new Email($model->email);

        $this->registerUserPolicy->assertEmailAvailableIo($email);

        $user = UserModel::create(
            email: $email,
            passwordHash: $this->passwordHasher->hash($model->password),
            fname: $model->fname,
            lname: $model->lname,
            city: $model->location?->city,
            phone: $model->phone,
        );

        $savedUser = $this->transactionManager->run(function () use ($user): UserModel {
            $savedUser = $this->userRepository->save($user);

            $this->preferenceRepository->save(
                NotificationPreferenceModel::createDefaultUserUpdated($savedUser->id),
            );

            return $savedUser;
        });

        $this->userLifecycleService->afterUserPersisted($savedUser, created: true);

        return UserRegisterModelMapper::fromModel($savedUser);
    }
}
