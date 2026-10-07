<?php

declare(strict_types=1);

namespace App\Application\Policy\User;

use App\Application\Exception\User\UnauthorizedUserAccessException;

final readonly class UserAccessPolicy
{
    public function assertSelfAccess(string $userId, string $currentUserId): void
    {
        if ($userId !== $currentUserId) {
            throw UnauthorizedUserAccessException::create();
        }
    }
}
