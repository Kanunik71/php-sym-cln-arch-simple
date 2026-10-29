<?php

declare(strict_types=1);

namespace App\Application\Service\User\Action;

use App\Application\Mapper\User\UserRegisterModelMapper;
use App\Application\Model\User\Action\UserRegisterModel;
use App\Application\Model\User\Read\UserRegisterResponseModel;
use App\Application\Model\User\UserModel;
use App\Application\Policy\User\PasswordPolicy;
use App\Application\Policy\User\RegisterUserPolicy;
use App\Application\Port\PasswordHasherInterface;
use App\Application\Port\User\UserRepositoryInterface;
use App\Application\Service\User\Lifecycle\UserLifecycleService;
use App\Application\ValueObject\Common\Email;

final readonly class UserRegisterService
{
    public function __construct(
        private RegisterUserPolicy $registerUserPolicy,
        private PasswordPolicy $passwordPolicy,
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private UserLifecycleService $userLifecycleService,
    ) {
    }

    public function execute(UserRegisterModel $model): UserRegisterResponseModel
    {
        $email = new Email($model->email);

        $this->registerUserPolicy->assertEmailAvailableIo($email);
        $this->passwordPolicy->assertValid($model->password);

        $user = UserModel::create(
            email: $email,
            passwordHash: $this->passwordHasher->hash($model->password),
            fname: $model->fname,
            lname: $model->lname,
            city: $model->location?->city,
            phone: $model->phone,
        );

        $savedUser = $this->userRepository->save($user);

        $this->userLifecycleService->afterUserPersisted($savedUser, created: true);

        return UserRegisterModelMapper::fromModel($savedUser);
    }
}
