<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\User\Action;

use App\Application\Exception\User\InvalidCredentialsException;
use App\Application\Model\User\Action\UserLoginModel;
use App\Application\Service\User\Action\UserLoginService;
use App\Tests\DatabaseTestCase;

final class UserLoginServiceTest extends DatabaseTestCase
{
    public function testIssuesTokenPairForValidCredentials(): void
    {
        $this->bed->createUser(email: 'john@example.com', password: 'secret123');

        $result = $this->bed->get(UserLoginService::class)->execute(
            new UserLoginModel(email: 'john@example.com', password: 'secret123'),
        );

        $this->assertNotEmpty($result->token);
        $this->assertNotEmpty($result->refreshToken);
        $this->assertSame('Bearer', $result->tokenType);
        $this->assertSame(900, $result->expiresIn);
    }

    public function testThrowsWhenPasswordIsWrong(): void
    {
        $this->bed->createUser(email: 'john@example.com', password: 'secret123');

        $this->expectException(InvalidCredentialsException::class);

        $this->bed->get(UserLoginService::class)->execute(
            new UserLoginModel(email: 'john@example.com', password: 'wrong-password'),
        );
    }

    public function testThrowsWhenEmailIsUnknown(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $this->bed->get(UserLoginService::class)->execute(
            new UserLoginModel(email: 'nobody@example.com', password: 'secret123'),
        );
    }
}
