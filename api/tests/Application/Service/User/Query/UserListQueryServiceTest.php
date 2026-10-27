<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\User\Query;

use App\Application\Model\Common\PaginationModel;
use App\Application\Service\User\Query\UserListQueryService;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsUserModelTrait;
use DateTimeImmutable;

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

        $page = $this->bed->get(UserListQueryService::class)->execute(new PaginationModel(page: 1, perPage: 20));

        $users = $page->items;

        $this->assertSame(2, $page->total);
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

    public function testPaginatesByCreatedAtDesc(): void
    {
        $this->bed->createUser(
            email: 'older@example.com',
            createdAt: new DateTimeImmutable('2023-01-01T00:00:00+00:00'),
        );
        $this->bed->createUser(
            email: 'newer@example.com',
            createdAt: new DateTimeImmutable('2025-01-01T00:00:00+00:00'),
        );

        $page = $this->bed->get(UserListQueryService::class)->execute(new PaginationModel(page: 1, perPage: 1));

        $this->assertSame(2, $page->total);
        $this->assertSame(1, $page->page);
        $this->assertSame(1, $page->perPage);
        $this->assertCount(1, $page->items);
        $this->assertSame('newer@example.com', $page->items[0]->email);
    }
}
