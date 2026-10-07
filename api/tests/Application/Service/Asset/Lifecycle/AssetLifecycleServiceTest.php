<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Asset\Lifecycle;

use App\Application\Service\Asset\Lifecycle\AssetLifecycleService;
use App\Tests\DatabaseTestCase;

final class AssetLifecycleServiceTest extends DatabaseTestCase
{
    public function testDeletesAsset(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');
        $asset = $this->bed->createAssetForTask($task->getId(), $user->getId(), 'Laptop', 999.99);
        $assetId = $asset->id;

        $lifecycle = $this->bed->get(AssetLifecycleService::class);

        $lifecycle->delete($asset);

        $this->bed->clear();
        $this->assertNull($this->bed->findAssetById($assetId));
    }
}
