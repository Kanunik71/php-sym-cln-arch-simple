<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\User\Query;

use App\Application\Service\User\Query\UserQueryService;
use App\Application\Exception\User\UserNotFoundException;
use App\Shared\Utils\UidUtils;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsUserModelTrait;

final class UserQueryServiceTest extends DatabaseTestCase
{
    use AssertsUserModelTrait;

    public function testReturnsMappedUser(): void
    {
        $user = $this->bed->createUser(
            email: 'demo@example.com',
            fname: 'Demo',
            lname: 'User',
            city: 'Paris',
        );

        $getUser = $this->bed->get(UserQueryService::class);

        $result = $getUser->execute(userId: $user->getId());

        $this->assertUserModelMatches($user, $result);
    }

    public function testReturnsAnotherUser(): void
    {
        $this->bed->createUser(email: 'demo@example.com');
        $otherUser = $this->bed->createUser(
            email: 'other@example.com',
            fname: 'Other',
            lname: 'User',
        );

        $getUser = $this->bed->get(UserQueryService::class);

        $result = $getUser->execute(userId: $otherUser->getId());

        $this->assertSame($otherUser->getEmail()->toString(), $result->email);
        $this->assertUserModelMatches($otherUser, $result);
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $getUser = $this->bed->get(UserQueryService::class);

        $this->expectException(UserNotFoundException::class);

        $getUser->execute(userId: UidUtils::nil());
    }
}
