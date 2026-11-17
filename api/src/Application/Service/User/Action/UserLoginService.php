<?php

declare(strict_types=1);

namespace App\Application\Service\User\Action;

use App\Application\Model\User\Action\UserLoginModel;
use App\Application\Model\User\Read\UserLoginResponseModel;
use App\Application\Policy\User\LoginUserPolicy;
use App\Application\Port\TransactionManagerInterface;
use App\Application\Service\Auth\UserTokenPairService;

final readonly class UserLoginService
{
    public function __construct(
        private LoginUserPolicy $loginUserPolicy,
        private UserTokenPairService $userTokenPairService,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function execute(UserLoginModel $model): UserLoginResponseModel
    {
        $user = $this->loginUserPolicy->resolveAuthenticatedUserIo($model->email, $model->password);

        return $this->transactionManager->run(
            fn (): UserLoginResponseModel => $this->userTokenPairService->issue($user),
        );
    }
}
