<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Asset\Action;

use App\Application\Service\Asset\Action\AssetAttachToTaskService;
use App\Application\Service\Task\Action\TaskChangeStatusService;
use App\Application\Model\Task\Action\TaskChangeStatusModel;
use App\Application\Service\Asset\Query\AssetQueryService;
use App\Application\Enum\Asset\AssetTypeEnum;
use App\Application\Enum\Task\TaskStatusEnum;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsAssetModelTrait;

final class AssetAttachToTaskServiceTest extends DatabaseTestCase
{
    use AssertsAssetModelTrait;

    public function testAttachesExistingAssetAndInProgressFollowsStatuses(): void
    {
        $user = $this->bed->seedDefaultUser();
        $activeTask = $this->bed->createActiveTask($user->getId(), 'Active task', 60);
        $finishedTask = $this->bed->createFinishedTask($user->getId(), 'Finished task', 30);
        $asset = $this->bed->createAssetForTask(
            $activeTask->getId(),
            $user->getId(),
            'Shared server',
            2500,
            AssetTypeEnum::Virtual,
        );

        $attachAsset = $this->bed->get(AssetAttachToTaskService::class);

        $linked = $attachAsset->execute(
            userId: $user->getId(),
            taskId: $finishedTask->getId(),
            assetId: $asset->id,
        );

        $this->assertSame([$activeTask->getId(), $finishedTask->getId()], $linked->taskIds);
        $this->assertTrue($linked->inProgress);

        $this->bed->clear();
        $persisted = $this->bed->findAssetById($asset->id);
        $this->assertNotNull($persisted);
        $this->assertAssetModelMatches($persisted, $linked);

        $changeTaskStatus = $this->bed->get(TaskChangeStatusService::class);
        $changeTaskStatus->execute(
            new TaskChangeStatusModel(status: TaskStatusEnum::Canceled, cancelReason: 'No longer needed'),
            taskId: $activeTask->getId(),
            userId: $user->getId(),
        );

        $getAsset = $this->bed->get(AssetQueryService::class);
        $afterCancel = $getAsset->execute(
            userId: $user->getId(),
            assetId: $asset->id,
        );

        $this->assertFalse($afterCancel->inProgress);
    }
}
