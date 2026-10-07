<?php

declare(strict_types=1);

namespace App\Application\Service\Asset\Query;

use App\Application\Model\Asset\Read\AssetViewModel;
use App\Application\Port\Asset\AssetRepositoryInterface;

final readonly class AssetListQueryService
{
    public function __construct(
        private AssetRepositoryInterface $assetRepository,
    ) {
    }

    /** @return list<AssetViewModel> */
    public function execute(string $userId): array
    {
        return $this->assetRepository->listViewsByOwnerId($userId);
    }
}
