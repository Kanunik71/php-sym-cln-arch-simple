<?php

declare(strict_types=1);

namespace App\Application\Service\User\Action;

use App\Application\Exception\Auth\InvalidRefreshTokenException;
use App\Application\Model\User\Action\UserRefreshModel;
use App\Application\Model\User\Read\UserLoginResponseModel;
use App\Application\Port\User\UserRepositoryInterface;
use App\Application\Service\Auth\RefreshTokenService;
use App\Application\Service\Auth\UserTokenPairService;

final readonly class UserRefreshService
{
    public function __construct(
        private RefreshTokenService $refreshTokenService,
        private UserRepositoryInterface $userRepository,
        private UserTokenPairService $userTokenPairService,
    ) {
    }

    public function execute(UserRefreshModel $model): UserLoginResponseModel
    {
        $issued = $this->refreshTokenService->rotate($model->refreshToken);
        $user = $this->userRepository->findById($issued->userId);

        if ($user === null) {
            throw InvalidRefreshTokenException::create();
        }

        return $this->userTokenPairService->build($user, $issued->token);
    }
}
