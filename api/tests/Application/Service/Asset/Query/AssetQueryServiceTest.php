<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Asset\Query;

use App\Application\Service\Asset\Query\AssetQueryService;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsAssetDetailModelTrait;

final class AssetQueryServiceTest extends DatabaseTestCase
{
    use AssertsAssetDetailModelTrait;

    public function testReturnsMappedAssetDetail(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');
        $asset = $this->bed->createAssetForTask($task->getId(), $user->getId(), 'Laptop', 999.99);

        $getAsset = $this->bed->get(AssetQueryService::class);

        $result = $getAsset->execute(
            userId: $user->getId(),
            assetId: $asset->id,
        );

        $this->assertSame($asset->id, $result->id);
        $this->assertSame('Laptop', $result->name);
        $this->assertAssetDetailModelMatches($asset, $result, $task);
    }
}
