<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\User\Query;

use App\Application\Model\User\Read\UserPageModel;
use App\Application\Model\User\Read\UserViewModel;
use App\Application\Enum\Common\FilterOperatorEnum;
use App\Application\Enum\Common\SortDirectionEnum;
use App\Application\Enum\User\UserFilterFieldEnum;
use App\Application\Enum\User\UserSortFieldEnum;
use App\Application\Model\Common\Filter\DateTimeFilterModel;
use App\Application\Model\Common\Filter\StringFilterModel;
use App\Application\Model\Common\PaginationModel;
use App\Application\Model\Common\SortModel;
use App\Application\Model\User\Action\UserSearchModel;
use App\Application\Model\User\UserFilterCriterionModel;
use App\Application\Service\User\Query\UserSearchQueryService;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsUserModelTrait;
use DateTimeImmutable;
use InvalidArgumentException;

final class UserSearchQueryServiceTest extends DatabaseTestCase
{
    use AssertsUserModelTrait;

    public function testReturnsMappedPage(): void
    {
        $firstUser = $this->bed->createUser(
            email: 'other@example.com',
            fname: 'Other',
            lname: 'User',
            city: 'Moscow',
        );
        $this->bed->createUser(
            email: 'demo@example.com',
            fname: 'Demo',
            lname: 'User',
        );

        $page = $this->searchUsers(
            sort: new SortModel(UserSortFieldEnum::Email->value, SortDirectionEnum::Asc),
        );

        $this->assertSame(2, $page->total);
        $this->assertSame(1, $page->page);
        $this->assertSame(20, $page->perPage);
        $this->assertCount(2, $page->items);
        $this->assertUserModelMatches($firstUser, $page->items[1]);
    }

    public function testAppliesDefaultSortWhenModelSortIsNull(): void
    {
        $this->bed->createUser(
            email: 'older@example.com',
            createdAt: new DateTimeImmutable('2023-01-01T00:00:00+00:00'),
        );
        $this->bed->createUser(
            email: 'newer@example.com',
            createdAt: new DateTimeImmutable('2025-01-01T00:00:00+00:00'),
        );

        $page = $this->searchUsers(sort: null);

        $this->assertSame('newer@example.com', $page->items[0]->email);
        $this->assertSame('older@example.com', $page->items[1]->email);
    }

    public function testRejectsInvalidSortField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "sort" must be one of');

        $this->searchUsers(
            sort: new SortModel('password', SortDirectionEnum::Asc),
        );
    }

    public function testFilterByEmail(): void
    {
        $this->bed->createUser(email: 'target@example.com', fname: 'Target');
        $this->bed->createUser(email: 'other@example.com', fname: 'Other');

        $page = $this->searchUsers(
            criteria: [
                new UserFilterCriterionModel(
                    UserFilterFieldEnum::Email,
                    new StringFilterModel(FilterOperatorEnum::Eq, value: 'target@example.com'),
                ),
            ],
        );

        $this->assertSame(1, $page->total);
        $this->assertCount(1, $page->items);
        $this->assertSame('target@example.com', $page->items[0]->email);
    }

    public function testFilterByFname(): void
    {
        $this->bed->createUser(email: 'ivan@example.com', fname: 'Ivan');
        $this->bed->createUser(email: 'anna@example.com', fname: 'Anna');

        $page = $this->searchUsers(
            criteria: [
                new UserFilterCriterionModel(
                    UserFilterFieldEnum::Fname,
                    new StringFilterModel(FilterOperatorEnum::Eq, value: 'Ivan'),
                ),
            ],
        );

        $this->assertSame(1, $page->total);
        $this->assertSame('ivan@example.com', $page->items[0]->email);
    }

    public function testFilterByLname(): void
    {
        $this->bed->createUser(email: 'petrov@example.com', lname: 'Petrov');
        $this->bed->createUser(email: 'schmidt@example.com', lname: 'Schmidt');

        $page = $this->searchUsers(
            criteria: [
                new UserFilterCriterionModel(
                    UserFilterFieldEnum::Lname,
                    new StringFilterModel(FilterOperatorEnum::Eq, value: 'Petrov'),
                ),
            ],
        );

        $this->assertSame(1, $page->total);
        $this->assertSame('petrov@example.com', $page->items[0]->email);
    }

    public function testFilterByCity(): void
    {
        $this->bed->createUser(email: 'moscow@example.com', city: 'Moscow');
        $this->bed->createUser(email: 'berlin@example.com', city: 'Berlin');

        $page = $this->searchUsers(
            criteria: [
                new UserFilterCriterionModel(
                    UserFilterFieldEnum::City,
                    new StringFilterModel(FilterOperatorEnum::Eq, value: 'Moscow'),
                ),
            ],
        );

        $this->assertSame(1, $page->total);
        $this->assertSame('moscow@example.com', $page->items[0]->email);
    }

    public function testFilterByCreatedAt(): void
    {
        $createdAt = new DateTimeImmutable('2024-01-15T12:00:00+00:00');
        $this->bed->createUser(email: 'dated@example.com', createdAt: $createdAt);
        $this->bed->createUser(
            email: 'other-date@example.com',
            createdAt: new DateTimeImmutable('2024-06-01T12:00:00+00:00'),
        );

        $page = $this->searchUsers(
            criteria: [
                new UserFilterCriterionModel(
                    UserFilterFieldEnum::CreatedAt,
                    new DateTimeFilterModel(FilterOperatorEnum::Eq, value: $createdAt),
                ),
            ],
        );

        $this->assertSame(1, $page->total);
        $this->assertSame('dated@example.com', $page->items[0]->email);
    }

    public function testFilterByTaskId(): void
    {
        $assignedUser = $this->bed->createUser(email: 'assigned@example.com');
        $this->bed->createUser(email: 'other@example.com');

        $task = $this->bed->createActiveTask($assignedUser->getId(), name: 'Assigned task');

        $page = $this->searchUsers(
            criteria: [
                new UserFilterCriterionModel(
                    UserFilterFieldEnum::TaskId,
                    new StringFilterModel(FilterOperatorEnum::Eq, value: $task->getId()),
                ),
            ],
        );

        $this->assertSame(1, $page->total);
        $this->assertSame($assignedUser->getId(), $page->items[0]->id);
    }

    public function testSortByEmail(): void
    {
        $this->bed->createUser(email: 'aaa-sort@example.com');
        $this->bed->createUser(email: 'zzz-sort@example.com');

        $this->assertSortOrder(
            field: UserSortFieldEnum::Email,
            ascFirstEmail: 'aaa-sort@example.com',
            ascSecondEmail: 'zzz-sort@example.com',
        );
    }

    public function testSortByFname(): void
    {
        $this->bed->createUser(email: 'fname-a@example.com', fname: 'Alice');
        $this->bed->createUser(email: 'fname-z@example.com', fname: 'Zoya');

        $this->assertSortOrder(
            field: UserSortFieldEnum::Fname,
            ascFirstEmail: 'fname-a@example.com',
            ascSecondEmail: 'fname-z@example.com',
        );
    }

    public function testSortByLname(): void
    {
        $this->bed->createUser(email: 'lname-a@example.com', lname: 'Adams');
        $this->bed->createUser(email: 'lname-z@example.com', lname: 'Zimmerman');

        $this->assertSortOrder(
            field: UserSortFieldEnum::Lname,
            ascFirstEmail: 'lname-a@example.com',
            ascSecondEmail: 'lname-z@example.com',
        );
    }

    public function testSortByCity(): void
    {
        $this->bed->createUser(email: 'city-a@example.com', city: 'Amsterdam');
        $this->bed->createUser(email: 'city-z@example.com', city: 'Zurich');

        $this->assertSortOrder(
            field: UserSortFieldEnum::City,
            ascFirstEmail: 'city-a@example.com',
            ascSecondEmail: 'city-z@example.com',
        );
    }

    public function testSortByCreatedAt(): void
    {
        $this->bed->createUser(
            email: 'older@example.com',
            createdAt: new DateTimeImmutable('2023-01-01T00:00:00+00:00'),
        );
        $this->bed->createUser(
            email: 'newer@example.com',
            createdAt: new DateTimeImmutable('2025-01-01T00:00:00+00:00'),
        );

        $this->assertSortOrder(
            field: UserSortFieldEnum::CreatedAt,
            ascFirstEmail: 'older@example.com',
            ascSecondEmail: 'newer@example.com',
        );
    }

    public function testPagination(): void
    {
        $this->bed->createUser(email: 'page-a@example.com');
        $this->bed->createUser(email: 'page-b@example.com');
        $this->bed->createUser(email: 'page-c@example.com');
        $this->bed->createUser(email: 'page-d@example.com');

        $page = $this->searchUsers(
            pagination: new PaginationModel(page: 1, perPage: 2),
            sort: new SortModel(UserSortFieldEnum::Email->value, SortDirectionEnum::Asc),
        );

        $this->assertSame(1, $page->page);
        $this->assertSame(2, $page->perPage);
        $this->assertSame(4, $page->total);
        $this->assertCount(2, $page->items);
    }

    /**
     * @param list<UserFilterCriterionModel> $criteria
     */
    private function searchUsers(
        ?PaginationModel $pagination = null,
        ?SortModel $sort = null,
        array $criteria = [],
    ): UserPageModel {
        $searchUsers = $this->bed->get(UserSearchQueryService::class);

        return $searchUsers->execute(new UserSearchModel(
            pagination: $pagination ?? new PaginationModel(page: 1, perPage: 20),
            criteria: $criteria,
            sort: $sort,
        ));
    }

    private function assertSortOrder(
        UserSortFieldEnum $field,
        string $ascFirstEmail,
        string $ascSecondEmail,
    ): void {
        $ascPage = $this->searchUsers(
            sort: new SortModel($field->value, SortDirectionEnum::Asc),
        );
        $this->assertEmailOrder($ascPage, $ascFirstEmail, $ascSecondEmail);

        $descPage = $this->searchUsers(
            sort: new SortModel($field->value, SortDirectionEnum::Desc),
        );
        $this->assertEmailOrder($descPage, $ascSecondEmail, $ascFirstEmail);
    }

    private function assertEmailOrder(UserPageModel $page, string $firstEmail, string $secondEmail): void
    {
        $emails = array_map(
            static fn (UserViewModel $user): string => $user->email,
            $page->items,
        );

        $firstIndex = array_search($firstEmail, $emails, true);
        $secondIndex = array_search($secondEmail, $emails, true);

        $this->assertNotFalse($firstIndex, sprintf('Expected email "%s" in results', $firstEmail));
        $this->assertNotFalse($secondIndex, sprintf('Expected email "%s" in results', $secondEmail));
        $this->assertLessThan(
            $secondIndex,
            $firstIndex,
            sprintf('Expected "%s" before "%s"', $firstEmail, $secondEmail),
        );
    }
}
