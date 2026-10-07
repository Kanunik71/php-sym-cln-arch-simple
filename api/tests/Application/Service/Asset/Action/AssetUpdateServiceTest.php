<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Asset\Action;

use App\Application\Service\Asset\Action\AssetUpdateService;
use App\Application\Model\Asset\Action\AssetUpdateModel;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsAssetModelTrait;

final class AssetUpdateServiceTest extends DatabaseTestCase
{
    use AssertsAssetModelTrait;

    public function testUpdatesAsset(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');
        $asset = $this->bed->createAssetForTask($task->getId(), $user->getId(), 'Laptop', 999.99);

        $updateAsset = $this->bed->get(AssetUpdateService::class);

        $result = $updateAsset->execute(
            new AssetUpdateModel(name: 'Desktop', price: 1299.99),
            userId: $user->getId(),
            assetId: $asset->id,
        );

        $this->assertSame('Desktop', $result->name);
        $this->assertSame(1299.99, $result->price);

        $this->bed->clear();
        $persisted = $this->bed->findAssetById($asset->id);
        $this->assertNotNull($persisted);
        $this->assertAssetModelMatches($persisted, $result);
    }
}
