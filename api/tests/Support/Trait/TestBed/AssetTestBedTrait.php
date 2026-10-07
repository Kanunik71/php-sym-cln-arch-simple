<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait\TestBed;

use App\Application\Enum\Asset\AssetTypeEnum;
use App\Application\Model\Asset\AssetModel;
use App\Application\Port\Asset\AssetRepositoryInterface;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;

trait AssetTestBedTrait
{
    public function assetRepository(): AssetRepositoryInterface
    {
        return $this->assets;
    }

    /**
     * Persist any structurally valid AssetModel row (+ optional junction links).
     *
     * @param list<string> $taskIds
     * @param list<string> $imageFileIds
     */
    public function createAsset(
        string $ownerId,
        string $name = 'Asset',
        array $taskIds = [],
        float $price = 100.0,
        AssetTypeEnum $type = AssetTypeEnum::Physical,
        ?string $id = null,
        ?DateTimeImmutable $createdAt = null,
        array $imageFileIds = [],
    ): AssetModel {
        $now = $createdAt ?? new DateTimeImmutable();

        return $this->assets->save(
            new AssetModel(
                id: $id ?? UidUtils::generateString(),
                name: $name,
                price: $price,
                type: $type,
                ownerId: $ownerId,
                createdAt: $now,
                updatedAt: $now,
            ),
            taskIds: $taskIds,
            imageFileIds: $imageFileIds,
        );
    }

    public function createAssetForTask(
        string $taskId,
        string $ownerId,
        string $name = 'Asset',
        float $price = 100.0,
        AssetTypeEnum $type = AssetTypeEnum::Physical,
    ): AssetModel {
        return $this->createAsset(
            ownerId: $ownerId,
            name: $name,
            taskIds: [$taskId],
            price: $price,
            type: $type,
        );
    }

    public function linkAssetToTask(AssetModel $asset, string $taskId): AssetModel
    {
        $this->assets->attachTask($asset->id, $taskId);

        return $this->assets->findOrFail($asset->id);
    }

    public function findAssetById(string $id): ?AssetModel
    {
        return $this->assets->findById($id);
    }
}
