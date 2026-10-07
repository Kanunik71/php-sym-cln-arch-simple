<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Asset\Action;

use App\Application\Service\Asset\Action\AssetDeleteService;
use App\Tests\DatabaseTestCase;

final class AssetDeleteServiceTest extends DatabaseTestCase
{
    public function testDeletesAsset(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');
        $asset = $this->bed->createAssetForTask($task->getId(), $user->getId(), 'Laptop', 999.99);
        $assetId = $asset->id;

        $deleteAsset = $this->bed->get(AssetDeleteService::class);

        $deleteAsset->execute(
            userId: $user->getId(),
            assetId: $assetId,
        );

        $this->bed->clear();
        $this->assertNull($this->bed->findAssetById($assetId));
    }
}
