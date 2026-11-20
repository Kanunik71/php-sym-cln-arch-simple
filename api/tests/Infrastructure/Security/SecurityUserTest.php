<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Infrastructure\Security\SecurityUser;
use LogicException;
use PHPUnit\Framework\TestCase;

final class SecurityUserTest extends TestCase
{
    public function testUserIdentifierIsUserIdAndNotEmail(): void
    {
        $user = new SecurityUser(
            id: '00000000-0000-7000-8000-000000000001',
            email: 'foo@example.com',
        );

        self::assertSame('00000000-0000-7000-8000-000000000001', $user->getUserIdentifier());
        self::assertSame('00000000-0000-7000-8000-000000000001', $user->getId());
        self::assertSame('foo@example.com', $user->getEmail());
    }

    public function testEmptyIdRejectsIdentifier(): void
    {
        $user = new SecurityUser(id: '', email: 'foo@example.com');

        $this->expectException(LogicException::class);

        $user->getUserIdentifier();
    }
}
