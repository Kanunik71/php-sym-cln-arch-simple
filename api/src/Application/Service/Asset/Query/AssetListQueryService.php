<?php

declare(strict_types=1);

namespace App\Application\Service\Asset\Query;

use App\Application\Model\Asset\Read\AssetPageModel;
use App\Application\Model\Common\PaginationModel;
use App\Application\Port\Asset\AssetRepositoryInterface;

final readonly class AssetListQueryService
{
    public function __construct(
        private AssetRepositoryInterface $assetRepository,
    ) {
    }

    public function execute(string $userId, PaginationModel $pagination): AssetPageModel
    {
        return $this->assetRepository->listViewPageByOwnerId($userId, $pagination);
    }
}
