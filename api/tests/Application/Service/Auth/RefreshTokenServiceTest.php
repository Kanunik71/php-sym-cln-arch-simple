<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Auth;

use App\Application\Exception\Auth\InvalidRefreshTokenException;
use App\Application\Model\Auth\RefreshTokenModel;
use App\Application\Port\Auth\RefreshTokenRepositoryInterface;
use App\Application\Service\Auth\RefreshTokenService;
use App\Shared\Utils\UidUtils;
use App\Tests\DatabaseTestCase;
use DateTimeImmutable;

final class RefreshTokenServiceTest extends DatabaseTestCase
{
    public function testIssueAndRotate(): void
    {
        $user = $this->bed->createUser(email: 'refresh@example.com');
        $service = $this->bed->get(RefreshTokenService::class);

        $issued = $service->issue($user->id);
        $this->assertStringStartsWith('rt_', $issued->token);

        $rotated = $service->rotate($issued->token);
        $this->assertNotSame($issued->token, $rotated->token);
        $this->assertSame($user->id, $rotated->userId);
    }

    public function testRotatedTokenCannotBeReusedAndRevokesFamily(): void
    {
        $user = $this->bed->createUser(email: 'reuse@example.com');
        $service = $this->bed->get(RefreshTokenService::class);

        $issued = $service->issue($user->id);
        $rotated = $service->rotate($issued->token);

        try {
            $service->rotate($issued->token);
            $this->fail('Expected InvalidRefreshTokenException.');
        } catch (InvalidRefreshTokenException) {
        }

        $this->expectException(InvalidRefreshTokenException::class);
        $service->rotate($rotated->token);
    }

    public function testExpiredTokenIsRejected(): void
    {
        $user = $this->bed->createUser(email: 'expired@example.com');
        $repository = $this->bed->get(RefreshTokenRepositoryInterface::class);
        $service = $this->bed->get(RefreshTokenService::class);

        $plainToken = 'rt_expired-token';
        $repository->save(RefreshTokenModel::create(
            userId: $user->id,
            tokenHash: RefreshTokenService::hash($plainToken),
            familyId: UidUtils::generateString(),
            expiresAt: new DateTimeImmutable('-1 minute'),
        ));

        $this->expectException(InvalidRefreshTokenException::class);
        $service->rotate($plainToken);
    }

    public function testRevokeInvalidatesFamily(): void
    {
        $user = $this->bed->createUser(email: 'logout@example.com');
        $service = $this->bed->get(RefreshTokenService::class);

        $issued = $service->issue($user->id);
        $service->revoke($issued->token);

        $this->expectException(InvalidRefreshTokenException::class);
        $service->rotate($issued->token);
    }

    public function testRevokeAllForUser(): void
    {
        $user = $this->bed->createUser(email: 'password-change@example.com');
        $service = $this->bed->get(RefreshTokenService::class);

        $first = $service->issue($user->id);
        $second = $service->issue($user->id);
        $service->revokeAllForUser($user->id);

        foreach ([$first, $second] as $issued) {
            try {
                $service->rotate($issued->token);
                $this->fail('Expected InvalidRefreshTokenException.');
            } catch (InvalidRefreshTokenException) {
            }
        }

        $this->addToAssertionCount(1);
    }

    public function testCleanupDeletesExpiredTokens(): void
    {
        $user = $this->bed->createUser(email: 'cleanup@example.com');
        $repository = $this->bed->get(RefreshTokenRepositoryInterface::class);
        $service = $this->bed->get(RefreshTokenService::class);

        $plainToken = 'rt_cleanup-token';
        $repository->save(RefreshTokenModel::create(
            userId: $user->id,
            tokenHash: RefreshTokenService::hash($plainToken),
            familyId: UidUtils::generateString(),
            expiresAt: new DateTimeImmutable('-8 days'),
        ));

        $this->assertGreaterThanOrEqual(1, $service->cleanup());
        $this->assertNull($repository->findByHash(RefreshTokenService::hash($plainToken)));
    }
}
