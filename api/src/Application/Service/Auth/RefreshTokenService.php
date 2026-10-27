<?php

declare(strict_types=1);

namespace App\Application\Service\Auth;

use App\Application\Exception\Auth\InvalidRefreshTokenException;
use App\Application\Model\Auth\RefreshTokenIssuedModel;
use App\Application\Model\Auth\RefreshTokenModel;
use App\Application\Port\Auth\RefreshTokenRepositoryInterface;
use App\Application\Port\TransactionManagerInterface;
use App\Shared\Utils\UidUtils;
use DateInterval;
use DateTimeImmutable;

final readonly class RefreshTokenService
{
    private const PREFIX = 'rt_';

    private const RANDOM_BYTES = 32;

    public function __construct(
        private RefreshTokenRepositoryInterface $repository,
        private TransactionManagerInterface $transactionManager,
        private int $ttlSeconds,
        private int $cleanupRetentionSeconds,
    ) {
    }

    public function issue(string $userId): RefreshTokenIssuedModel
    {
        return $this->transactionManager->run(
            fn (): RefreshTokenIssuedModel => $this->createToken(
                userId: $userId,
                familyId: UidUtils::generateString(),
                now: new DateTimeImmutable(),
            ),
        );
    }

    public function rotate(string $plainToken): RefreshTokenIssuedModel
    {
        $issued = $this->transactionManager->run(function () use ($plainToken): ?RefreshTokenIssuedModel {
            $now = new DateTimeImmutable();
            $stored = $this->repository->findByHash(self::hash($plainToken));

            if ($stored === null) {
                return null;
            }

            if ($stored->isRevoked()) {
                $this->repository->revokeFamily($stored->familyId, $now);

                return null;
            }

            if (!$stored->isActive($now)) {
                return null;
            }

            if (!$this->repository->revokeIfActive($stored->id, $now)) {
                $this->repository->revokeFamily($stored->familyId, $now);

                return null;
            }

            return $this->createToken(
                userId: $stored->userId,
                familyId: $stored->familyId,
                now: $now,
            );
        });

        if ($issued === null) {
            throw InvalidRefreshTokenException::create();
        }

        return $issued;
    }

    public function revoke(string $plainToken): void
    {
        $stored = $this->repository->findByHash(self::hash($plainToken));

        if ($stored === null) {
            return;
        }

        $this->repository->revokeFamily($stored->familyId, new DateTimeImmutable());
    }

    public function revokeAllForUser(string $userId): void
    {
        $this->repository->revokeAllForUser($userId, new DateTimeImmutable());
    }

    public function cleanup(): int
    {
        $threshold = (new DateTimeImmutable())->sub(
            new DateInterval(sprintf('PT%dS', $this->cleanupRetentionSeconds)),
        );

        return $this->repository->deleteExpiredBefore($threshold);
    }

    public static function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    private function createToken(
        string $userId,
        string $familyId,
        DateTimeImmutable $now,
    ): RefreshTokenIssuedModel {
        $plainToken = self::generatePlainToken();
        $expiresAt = $now->add(new DateInterval(sprintf('PT%dS', $this->ttlSeconds)));

        $this->repository->save(RefreshTokenModel::create(
            userId: $userId,
            tokenHash: self::hash($plainToken),
            familyId: $familyId,
            expiresAt: $expiresAt,
        ));

        return new RefreshTokenIssuedModel(
            userId: $userId,
            token: $plainToken,
            expiresAt: $expiresAt,
        );
    }

    private static function generatePlainToken(): string
    {
        $random = rtrim(strtr(base64_encode(random_bytes(self::RANDOM_BYTES)), '+/', '-_'), '=');

        return self::PREFIX.$random;
    }
}
