<?php

declare(strict_types=1);

namespace App\Application\Service\Asset\Action;

use App\Application\Model\Asset\Read\AssetViewModel;
use App\Application\Model\Asset\Action\AssetUpdateModel;
use App\Application\Policy\Asset\AssetAccessPolicy;
use App\Application\Port\Asset\AssetRepositoryInterface;
use App\Application\Service\File\Lifecycle\FileLifecycleService;

final readonly class AssetUpdateService
{
    public function __construct(
        private AssetAccessPolicy $assetAccessPolicy,
        private AssetRepositoryInterface $assetRepository,
        private FileLifecycleService $fileLifecycleService,
    ) {
    }

    public function execute(AssetUpdateModel $model, string $userId, string $assetId): AssetViewModel
    {
        $asset = $this->assetRepository->findOrFail($assetId);

        $this->assetAccessPolicy->assertUserCanAccess($asset, $userId);

        $oldFileIds = $this->assetRepository->listImageFileIds($asset->id);

        $updated = $asset->withNameAndPrice($model->name, $model->price);

        $savedAsset = $this->assetRepository->save(
            $updated,
            imageFileIds: $model->imageFileIds,
        );

        $this->fileLifecycleService->syncFileIds(
            $oldFileIds,
            $model->imageFileIds,
            $userId,
        );

        return $this->assetRepository->findViewOrFail($savedAsset->id);
    }
}
