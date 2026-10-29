<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Asset\Query;

use App\Application\Model\Common\PaginationModel;
use App\Application\Service\Asset\Query\AssetListQueryService;
use App\Tests\DatabaseTestCase;
use DateTimeImmutable;

final class AssetListQueryServiceTest extends DatabaseTestCase
{
    public function testReturnsAssetsForUser(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(ownerId: $user->getId(), name: 'Build feature');
        $asset = $this->bed->createAssetForTask($task->getId(), $user->getId(), 'Laptop', 999.99);

        $this->bed->clear();

        $listAssets = $this->bed->get(AssetListQueryService::class);

        $page = $listAssets->execute(
            userId: $user->getId(),
            pagination: new PaginationModel(page: 1, perPage: 20),
        );

        $this->assertSame(1, $page->total);
        $this->assertSame(1, $page->page);
        $this->assertSame(20, $page->perPage);
        $this->assertCount(1, $page->items);
        $this->assertSame($asset->id, $page->items[0]->id);
    }

    public function testPaginatesByCreatedAtThenId(): void
    {
        $user = $this->bed->seedDefaultUser();
        $other = $this->bed->createUser();
        $older = $this->bed->createAsset(
            ownerId: $user->getId(),
            name: 'Older',
            createdAt: new DateTimeImmutable('2026-01-01 00:00:00'),
        );
        $newer = $this->bed->createAsset(
            ownerId: $user->getId(),
            name: 'Newer',
            createdAt: new DateTimeImmutable('2026-02-01 00:00:00'),
        );
        $this->bed->createAsset(ownerId: $other->getId(), name: 'Foreign');

        $listAssets = $this->bed->get(AssetListQueryService::class);

        $first = $listAssets->execute(
            userId: $user->getId(),
            pagination: new PaginationModel(page: 1, perPage: 1),
        );
        $second = $listAssets->execute(
            userId: $user->getId(),
            pagination: new PaginationModel(page: 2, perPage: 1),
        );

        $this->assertSame(2, $first->total);
        $this->assertSame([$newer->id], array_map(static fn ($item) => $item->id, $first->items));
        $this->assertSame([$older->id], array_map(static fn ($item) => $item->id, $second->items));
    }
}
