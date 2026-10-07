<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Asset\Action;

use App\Application\Service\Asset\Action\AssetDetachFromTaskService;
use App\Application\Enum\Asset\AssetTypeEnum;
use App\Tests\DatabaseTestCase;

final class AssetDetachFromTaskServiceTest extends DatabaseTestCase
{
    public function testDeletesAssetWhenLastTaskDetached(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');
        $asset = $this->bed->createAssetForTask($task->getId(), $user->getId(), 'Laptop', 999.99);
        $assetId = $asset->id;

        $detachAsset = $this->bed->get(AssetDetachFromTaskService::class);

        $detachAsset->execute(
            userId: $user->getId(),
            taskId: $task->getId(),
            assetId: $assetId,
        );

        $this->bed->clear();
        $this->assertNull($this->bed->findAssetById($assetId));
    }

    public function testKeepsAssetWhenOtherTasksRemain(): void
    {
        $user = $this->bed->seedDefaultUser();
        $firstTask = $this->bed->createTask(name: 'First task');
        $secondTask = $this->bed->createTask(name: 'Second task');
        $asset = $this->bed->createAssetForTask($firstTask->getId(), $user->getId(), 'Shared server', 2500, AssetTypeEnum::Virtual);
        $this->bed->linkAssetToTask($asset, $secondTask->getId());

        $detachAsset = $this->bed->get(AssetDetachFromTaskService::class);

        $detachAsset->execute(
            userId: $user->getId(),
            taskId: $firstTask->getId(),
            assetId: $asset->id,
        );

        $this->bed->clear();
        $persisted = $this->bed->findAssetById($asset->id);
        $this->assertNotNull($persisted);
        $this->assertSame(
            [$secondTask->getId()],
            $this->bed->assetRepository()->listTaskIds($persisted->id),
        );
    }
}
