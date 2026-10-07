<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Asset\Query;

use App\Application\Service\Asset\Query\AssetListQueryService;
use App\Tests\DatabaseTestCase;

final class AssetListQueryServiceTest extends DatabaseTestCase
{
    public function testReturnsAssetsForUser(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');
        $asset = $this->bed->createAssetForTask($task->getId(), $user->getId(), 'Laptop', 999.99);

        $this->bed->clear();

        $listAssets = $this->bed->get(AssetListQueryService::class);

        $assets = $listAssets->execute(userId: $user->getId());

        $this->assertCount(1, $assets);
        $this->assertSame($asset->id, $assets[0]->id);
    }
}
