<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Task\Query;

use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Model\Common\PaginationModel;
use App\Application\Service\Task\Query\TaskListQueryService;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsTaskModelTrait;
use DateTimeImmutable;

final class TaskListQueryServiceTest extends DatabaseTestCase
{
    use AssertsTaskModelTrait;

    public function testReturnsMappedTasks(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(ownerId: $user->getId(), name: 'Learn Symfony');

        $listTasks = $this->bed->get(TaskListQueryService::class);

        $page = $listTasks->execute(
            userId: $user->getId(),
            pagination: new PaginationModel(page: 1, perPage: 20),
        );

        $this->assertSame(1, $page->total);
        $this->assertSame(1, $page->page);
        $this->assertSame(20, $page->perPage);
        $this->assertCount(1, $page->items);
        $this->assertSame('Learn Symfony', $page->items[0]->name);
        $this->assertTaskModelMatches($task, $page->items[0]);
    }

    public function testPaginatesByCreatedAtThenId(): void
    {
        $user = $this->bed->seedDefaultUser();
        $older = $this->bed->createTask(
            ownerId: $user->getId(),
            name: 'Older',
            createdAt: new DateTimeImmutable('2026-01-01 00:00:00'),
        );
        $newer = $this->bed->createTask(
            ownerId: $user->getId(),
            name: 'Newer',
            createdAt: new DateTimeImmutable('2026-02-01 00:00:00'),
        );

        $listTasks = $this->bed->get(TaskListQueryService::class);

        $first = $listTasks->execute(
            userId: $user->getId(),
            pagination: new PaginationModel(page: 1, perPage: 1),
        );
        $second = $listTasks->execute(
            userId: $user->getId(),
            pagination: new PaginationModel(page: 2, perPage: 1),
        );

        $this->assertSame(2, $first->total);
        $this->assertSame([$newer->getId()], array_map(static fn ($item) => $item->id, $first->items));
        $this->assertSame([$older->getId()], array_map(static fn ($item) => $item->id, $second->items));
    }

    public function testIncludesFinishedTasks(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createFinishedTask($user->getId(), 'Done');

        $page = $this->bed->get(TaskListQueryService::class)->execute(
            userId: $user->getId(),
            pagination: new PaginationModel(page: 1, perPage: 20),
        );

        $this->assertSame(1, $page->total);
        $this->assertSame($task->getId(), $page->items[0]->id);
        $this->assertSame(TaskStatusEnum::Finished, $page->items[0]->status);
    }
}
