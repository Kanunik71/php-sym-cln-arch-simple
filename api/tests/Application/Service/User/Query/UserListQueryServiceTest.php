<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\User\Query;

use App\Application\Service\User\Query\UserListQueryService;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsUserModelTrait;

final class UserListQueryServiceTest extends DatabaseTestCase
{
    use AssertsUserModelTrait;

    public function testReturnsMappedUsers(): void
    {
        $firstUser = $this->bed->createUser(
            email: 'other@example.com',
            fname: 'Other',
            lname: 'User',
            city: 'Moscow',
        );
        $secondUser = $this->bed->createUser(
            email: 'demo@example.com',
            fname: 'Demo',
            lname: 'User',
        );

        $listUsers = $this->bed->get(UserListQueryService::class);

        $users = $listUsers->execute();

        $this->assertCount(2, $users);

        $byId = [];
        foreach ($users as $dto) {
            $byId[$dto->id] = $dto;
        }

        $this->assertArrayHasKey($firstUser->getId(), $byId);
        $this->assertArrayHasKey($secondUser->getId(), $byId);
        $this->assertUserModelMatches($firstUser, $byId[$firstUser->getId()]);
        $this->assertUserModelMatches($secondUser, $byId[$secondUser->getId()]);
    }
}
