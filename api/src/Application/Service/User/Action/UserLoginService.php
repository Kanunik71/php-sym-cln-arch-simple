<?php

declare(strict_types=1);

namespace App\Application\Service\User\Action;

use App\Application\Model\User\Action\UserLoginModel;
use App\Application\Model\User\Read\UserLoginResponseModel;
use App\Application\Policy\User\LoginUserPolicy;
use App\Application\Port\Security\TokenProviderInterface;

final readonly class UserLoginService
{
    public function __construct(
        private LoginUserPolicy $loginUserPolicy,
        private TokenProviderInterface $tokenProvider,
    ) {
    }

    public function execute(UserLoginModel $model): UserLoginResponseModel
    {
        $user = $this->loginUserPolicy->resolveAuthenticatedUserIo($model->email, $model->password);

        $token = $this->tokenProvider->generateToken($user->id, $user->email);

        return new UserLoginResponseModel(token: $token);
    }
}
