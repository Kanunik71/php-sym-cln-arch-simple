<?php

declare(strict_types=1);

namespace App\Application\Service\User\Action;

use App\Application\Model\User\Action\UserLogoutModel;
use App\Application\Service\Auth\RefreshTokenService;

final readonly class UserLogoutService
{
    public function __construct(
        private RefreshTokenService $refreshTokenService,
    ) {
    }

    public function execute(UserLogoutModel $model): void
    {
        $this->refreshTokenService->revoke($model->refreshToken);
    }
}
