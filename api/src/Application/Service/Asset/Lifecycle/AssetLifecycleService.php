<?php

declare(strict_types=1);

namespace App\Application\Service\Asset\Lifecycle;

use App\Application\Model\Asset\AssetModel;
use App\Application\Port\Asset\AssetRepositoryInterface;
use App\Application\Service\File\Lifecycle\FileLifecycleService;

final readonly class AssetLifecycleService
{
    public function __construct(
        private AssetRepositoryInterface $assetRepository,
        private FileLifecycleService $fileLifecycleService,
    ) {
    }

    public function delete(AssetModel $asset): void
    {
        $this->fileLifecycleService->purgeStorage($this->deleteRecords($asset));
    }

    /**
     * Deletes asset (+ owned file DB rows). Returns storage keys to purge after DB commit.
     *
     * @return list<string>
     */
    public function deleteRecords(AssetModel $asset): array
    {
        $fileIds = $this->assetRepository->listImageFileIds($asset->id);
        $this->assetRepository->delete($asset);

        return $this->fileLifecycleService->deleteRecords($fileIds);
    }
}
