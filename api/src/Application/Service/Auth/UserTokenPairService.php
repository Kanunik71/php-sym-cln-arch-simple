<?php

declare(strict_types=1);

namespace App\Application\Service\Auth;

use App\Application\Model\User\Read\UserLoginResponseModel;
use App\Application\Model\User\UserModel;
use App\Application\Port\Security\TokenProviderInterface;

final readonly class UserTokenPairService
{
    public function __construct(
        private TokenProviderInterface $tokenProvider,
        private RefreshTokenService $refreshTokenService,
        private int $accessTtl,
    ) {
    }

    public function issue(UserModel $user): UserLoginResponseModel
    {
        $refreshToken = $this->refreshTokenService->issue($user->id);

        return $this->build($user, $refreshToken->token);
    }

    public function build(UserModel $user, string $refreshToken): UserLoginResponseModel
    {
        return new UserLoginResponseModel(
            token: $this->tokenProvider->generateToken($user->id, $user->email),
            refreshToken: $refreshToken,
            tokenType: 'Bearer',
            expiresIn: $this->accessTtl,
        );
    }
}
