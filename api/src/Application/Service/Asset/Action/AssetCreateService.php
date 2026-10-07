<?php

declare(strict_types=1);

namespace App\Application\Service\Asset\Action;

use App\Application\Model\Asset\AssetModel;
use App\Application\Model\Asset\Read\AssetViewModel;
use App\Application\Model\Asset\Action\AssetCreateModel;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Port\Asset\AssetRepositoryInterface;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Service\File\Lifecycle\FileLifecycleService;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;

final readonly class AssetCreateService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private AssetRepositoryInterface $assetRepository,
        private FileLifecycleService $fileLifecycleService,
    ) {
    }

    public function execute(AssetCreateModel $model, string $userId, ?string $taskId = null): AssetViewModel
    {
        if ($taskId !== null) {
            return $this->createForTask($model, $userId, $taskId);
        }

        return $this->createWithoutTask($model, $userId);
    }

    private function createWithoutTask(AssetCreateModel $model, string $userId): AssetViewModel
    {

        foreach ($model->taskIds as $relatedTaskId) {
            $task = $this->taskRepository->findOrFail($relatedTaskId);
            $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);
        }

        return $this->process($model, $model->taskIds, $model->imageFileIds, $userId);
    }

    private function createForTask(AssetCreateModel $model, string $userId, string $taskId): AssetViewModel
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);        

        return $this->process($model, [$taskId], $model->imageFileIds, $userId);
    }

    /**
     * @param list<string> $taskIds
     * @param list<string> $imageFileIds
     */
    private function process(
        AssetCreateModel $model,
        array $taskIds,
        array $imageFileIds,
        string $userId,
    ): AssetViewModel {
        $now = new DateTimeImmutable();
        $asset = new AssetModel(
            id: UidUtils::generateString(),
            name: $model->name,
            price: $model->price,
            type: $model->type,
            ownerId: $userId,
            createdAt: $now,
            updatedAt: $now,
        );

        $savedAsset = $this->assetRepository->save($asset, $taskIds, $imageFileIds);

        $this->fileLifecycleService->attachFiles($imageFileIds, $userId);

        return $this->assetRepository->findViewOrFail($savedAsset->id);
    }
}
