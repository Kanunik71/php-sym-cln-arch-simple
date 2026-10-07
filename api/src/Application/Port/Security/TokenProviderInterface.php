<?php

declare(strict_types=1);

namespace App\Application\Port\Security;

interface TokenProviderInterface
{
    public function generateToken(string $userId, string $email): string;
}
