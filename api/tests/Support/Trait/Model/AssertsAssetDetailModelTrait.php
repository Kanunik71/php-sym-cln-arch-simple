<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait\Model;

use App\Application\Model\Asset\Read\AssetDetailModel;
use App\Application\Model\Asset\AssetModel;
use App\Application\Model\Task\TaskModel;
use App\Shared\Utils\DateUtils;
use App\Tests\Support\TestBed;

trait AssertsAssetDetailModelTrait
{
    /** @var TestBed */
    protected $bed;

    private function assertAssetDetailModelMatches(
        AssetModel $asset,
        AssetDetailModel $model,
        TaskModel ...$expectedTasks,
    ): void {
        $repo = $this->bed->assetRepository();

        $this->assertSame($asset->id, $model->id);
        $this->assertSame($asset->ownerId, $model->ownerId);
        $this->assertSame($asset->name, $model->name);
        $this->assertSame($asset->price, $model->price);
        $this->assertSame($asset->type, $model->type);
        $this->assertSame(DateUtils::toAtom($asset->createdAt), $model->createdAt);
        $this->assertCount(count($expectedTasks), $model->tasks);

        foreach (array_values($expectedTasks) as $index => $task) {
            $taskModel = $model->tasks[$index] ?? null;
            $this->assertNotNull($taskModel);
            $this->assertSame($task->getId(), $taskModel->id);
            $this->assertSame($task->getName(), $taskModel->name);
            $this->assertSame($task->getParentId(), $taskModel->parentId);
            $this->assertSame($task->getEstimateTime(), $taskModel->estimateTime);
            $this->assertSame($task->getStatus(), $taskModel->status);
            $this->assertSame(DateUtils::toAtom($task->getCreatedAt()), $taskModel->createdAt);
        }

        $imageFileIds = $repo->listImageFileIds($asset->id);
        $this->assertCount(count($imageFileIds), $model->images);

        foreach ($imageFileIds as $index => $fileId) {
            $imageModel = $model->images[$index] ?? null;
            $this->assertNotNull($imageModel);
            $this->assertSame($fileId, $imageModel->id);
            $this->assertStringContainsString($fileId, $imageModel->url);
        }
    }
}
