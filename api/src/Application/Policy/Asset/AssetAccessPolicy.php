<?php

declare(strict_types=1);

namespace App\Application\Policy\Asset;

use App\Application\Exception\Asset\UnauthorizedAssetAccessException;
use App\Application\Model\Asset\AssetModel;

final readonly class AssetAccessPolicy
{
    public function assertBelongsToTask(bool $belongsToTask): void
    {
        if (!$belongsToTask) {
            throw UnauthorizedAssetAccessException::create();
        }
    }

    public function assertOwner(string $ownerId, string $userId): void
    {
        if ($ownerId !== $userId) {
            throw UnauthorizedAssetAccessException::create();
        }
    }

    public function assertUserCanAccess(AssetModel $asset, string $userId): void
    {
        $this->assertOwner($asset->ownerId, $userId);
    }
}
