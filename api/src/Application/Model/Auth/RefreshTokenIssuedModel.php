<?php

declare(strict_types=1);

namespace App\Application\Model\Auth;

use DateTimeImmutable;

final readonly class RefreshTokenIssuedModel
{
    public function __construct(
        public string $userId,
        public string $token,
        public DateTimeImmutable $expiresAt,
    ) {
    }
}
