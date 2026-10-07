<?php

declare(strict_types=1);

namespace App\Application\Service\Asset\Query;

use App\Application\Model\Asset\Read\AssetDetailModel;
use App\Application\Policy\Asset\AssetAccessPolicy;
use App\Application\Port\Asset\AssetRepositoryInterface;

final readonly class AssetQueryService
{
    public function __construct(
        private AssetAccessPolicy $assetAccessPolicy,
        private AssetRepositoryInterface $assetRepository,
    ) {
    }

    public function execute(string $userId, string $assetId): AssetDetailModel
    {
        $detail = $this->assetRepository->findDetailOrFail($assetId);

        $this->assetAccessPolicy->assertOwner($detail->ownerId, $userId);

        return $detail;
    }
}
