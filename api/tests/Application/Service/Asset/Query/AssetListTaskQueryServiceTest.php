<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Asset\Query;

use App\Application\Service\Asset\Query\AssetListTaskQueryService;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsAssetModelTrait;

final class AssetListTaskQueryServiceTest extends DatabaseTestCase
{
    use AssertsAssetModelTrait;

    public function testReturnsMappedAssets(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');
        $asset = $this->bed->createAssetForTask($task->getId(), $user->getId(), 'Laptop', 999.99);
        $taskId = $task->getId();
        $assetId = $asset->id;

        $this->bed->clear();

        $listTaskAssets = $this->bed->get(AssetListTaskQueryService::class);

        $assets = $listTaskAssets->execute(
            userId: $user->getId(),
            taskId: $taskId,
        );

        $this->assertCount(1, $assets);
        $this->assertSame('Laptop', $assets[0]->name);
        $this->assertSame($assetId, $assets[0]->id);
    }
}
