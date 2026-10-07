<?php

declare(strict_types=1);

namespace App\Application\Service\Asset\Action;

use App\Application\Policy\Asset\AssetAccessPolicy;
use App\Application\Port\Asset\AssetRepositoryInterface;
use App\Application\Service\Asset\Lifecycle\AssetLifecycleService;

final readonly class AssetDeleteService
{
    public function __construct(
        private AssetAccessPolicy $assetAccessPolicy,
        private AssetRepositoryInterface $assetRepository,
        private AssetLifecycleService $assetLifecycleService,
    ) {
    }

    public function execute(string $userId, string $assetId): void
    {
        $asset = $this->assetRepository->findOrFail($assetId);

        $this->assetAccessPolicy->assertUserCanAccess($asset, $userId);

        $this->assetLifecycleService->delete($asset);
    }
}
