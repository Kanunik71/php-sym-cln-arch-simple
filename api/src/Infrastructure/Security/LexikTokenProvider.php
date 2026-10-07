<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Port\Security\TokenProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final readonly class LexikTokenProvider implements TokenProviderInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
    ) {
    }

    public function generateToken(string $userId, string $email): string
    {
        return $this->jwtManager->createFromPayload(new SecurityUser($userId, $email), [
            'email' => $email,
        ]);
    }
}
