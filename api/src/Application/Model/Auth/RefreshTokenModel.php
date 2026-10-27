<?php

declare(strict_types=1);

namespace App\Application\Model\Auth;

use App\Shared\Utils\UidUtils;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Persistence model — mirrors refresh_tokens columns (scalars only).
 */
final readonly class RefreshTokenModel
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $tokenHash,
        public string $familyId,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $expiresAt,
        public ?DateTimeImmutable $revokedAt = null,
    ) {
        if (preg_match('/^[0-9a-f]{64}$/', $tokenHash) !== 1) {
            throw new InvalidArgumentException('Refresh token hash must be a SHA-256 hex string.');
        }
    }

    public static function create(
        string $userId,
        string $tokenHash,
        string $familyId,
        DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            id: UidUtils::generateString(),
            userId: $userId,
            tokenHash: $tokenHash,
            familyId: $familyId,
            createdAt: new DateTimeImmutable(),
            expiresAt: $expiresAt,
        );
    }

    public function isActive(DateTimeImmutable $now): bool
    {
        return $this->revokedAt === null && $this->expiresAt > $now;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }
}
