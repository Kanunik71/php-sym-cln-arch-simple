<?php

declare(strict_types=1);

namespace App\Application\Service\Asset\Action;

use App\Application\Model\Asset\Read\AssetViewModel;
use App\Application\Policy\Asset\AssetAccessPolicy;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Port\Asset\AssetRepositoryInterface;
use App\Application\Port\Task\TaskRepositoryInterface;

final readonly class AssetAttachToTaskService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private AssetAccessPolicy $assetAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private AssetRepositoryInterface $assetRepository,
    ) {
    }

    public function execute(string $userId, string $taskId, string $assetId): AssetViewModel
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);

        $asset = $this->assetRepository->findOrFail($assetId);

        $this->assetAccessPolicy->assertUserCanAccess($asset, $userId);

        if ($this->assetRepository->belongsToTask($asset->id, $taskId)) {
            return $this->assetRepository->findViewOrFail($asset->id);
        }

        $this->assetRepository->attachTask($asset->id, $taskId);

        return $this->assetRepository->findViewOrFail($asset->id);
    }
}
