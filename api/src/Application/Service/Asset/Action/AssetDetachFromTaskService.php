<?php

declare(strict_types=1);

namespace App\Application\Service\Asset\Action;

use App\Application\Policy\Asset\AssetAccessPolicy;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Port\Asset\AssetRepositoryInterface;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Service\Asset\Lifecycle\AssetLifecycleService;

final readonly class AssetDetachFromTaskService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private AssetAccessPolicy $assetAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private AssetRepositoryInterface $assetRepository,
        private AssetLifecycleService $assetLifecycleService,
    ) {
    }

    public function execute(string $userId, string $taskId, string $assetId): void
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);

        $asset = $this->assetRepository->findOrFail($assetId);

        $this->assetAccessPolicy->assertBelongsToTask(
            $this->assetRepository->belongsToTask($asset->id, $taskId),
        );

        $taskIds = $this->assetRepository->listTaskIds($asset->id);

        if (count($taskIds) === 1) {
            $this->assetLifecycleService->delete($asset);

            return;
        }

        $this->assetRepository->detachTask($asset->id, $taskId);
    }
}
