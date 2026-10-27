<?php

declare(strict_types=1);

namespace App\Application\Port\Auth;

use App\Application\Model\Auth\RefreshTokenModel;
use DateTimeImmutable;

interface RefreshTokenRepositoryInterface
{
    public function save(RefreshTokenModel $token): RefreshTokenModel;

    public function findByHash(string $tokenHash): ?RefreshTokenModel;

    public function revokeIfActive(string $id, DateTimeImmutable $now): bool;

    public function revokeFamily(string $familyId, DateTimeImmutable $now): void;

    public function revokeAllForUser(string $userId, DateTimeImmutable $now): void;

    public function deleteAllForUser(string $userId): void;

    public function deleteExpiredBefore(DateTimeImmutable $threshold): int;
}
