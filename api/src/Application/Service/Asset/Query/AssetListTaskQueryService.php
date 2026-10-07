<?php

declare(strict_types=1);

namespace App\Application\Service\Asset\Query;

use App\Application\Model\Asset\Read\AssetViewModel;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Port\Asset\AssetRepositoryInterface;
use App\Application\Port\Task\TaskRepositoryInterface;

final readonly class AssetListTaskQueryService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private AssetRepositoryInterface $assetRepository,
    ) {
    }

    /** @return list<AssetViewModel> */
    public function execute(string $userId, string $taskId): array
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);

        return $this->assetRepository->listViewsByTaskId($taskId);
    }
}
