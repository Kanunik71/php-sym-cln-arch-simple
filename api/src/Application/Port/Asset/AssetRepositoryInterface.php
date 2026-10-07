<?php

declare(strict_types=1);

namespace App\Application\Port\Asset;

use App\Application\Model\Asset\Read\AssetDetailModel;
use App\Application\Model\Asset\AssetModel;
use App\Application\Model\Asset\Read\AssetViewModel;

/**
 * AssetModel mirrors assets row scalars only.
 * Task / file links are junction data — pass on save, or mutate via attachTask / detachTask / list*.
 */
interface AssetRepositoryInterface
{
    /**
     * @param list<string>|null $taskIds       null = leave existing links unchanged
     * @param list<string>|null $imageFileIds  null = leave existing links unchanged
     */
    public function save(
        AssetModel $asset,
        ?array $taskIds = null,
        ?array $imageFileIds = null,
    ): AssetModel;

    public function findById(string $id): ?AssetModel;

    public function findOrFail(string $id): AssetModel;

    public function findViewOrFail(string $id): AssetViewModel;

    /** Read model for GetAsset (tasks as TaskLightModel; one query). */
    public function findDetailOrFail(string $id): AssetDetailModel;

    /** @return list<AssetViewModel> */
    public function listViewsByOwnerId(string $ownerId): array;

    /** @return list<AssetViewModel> */
    public function listViewsByTaskId(string $taskId): array;

    /** @return list<AssetModel> */
    public function listByOwnerId(string $ownerId): array;

    /** @return list<string> */
    public function listTaskIds(string $assetId): array;

    /** @return list<string> */
    public function listImageFileIds(string $assetId): array;

    public function belongsToTask(string $assetId, string $taskId): bool;

    public function attachTask(string $assetId, string $taskId): void;

    public function detachTask(string $assetId, string $taskId): void;

    public function delete(AssetModel $asset): void;
}
