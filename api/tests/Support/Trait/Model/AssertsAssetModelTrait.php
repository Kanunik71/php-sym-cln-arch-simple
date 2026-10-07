<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait\Model;

use App\Application\Model\Asset\AssetModel;
use App\Application\Model\Asset\Read\AssetViewModel;
use App\Shared\Utils\DateUtils;
use App\Tests\Support\TestBed;

trait AssertsAssetModelTrait
{
    /** @var TestBed */
    protected $bed;

    private function assertAssetModelMatches(AssetModel $asset, AssetViewModel $view): void
    {
        $repo = $this->bed->assetRepository();

        $this->assertSame($asset->id, $view->id);
        $this->assertSame($asset->ownerId, $view->ownerId);
        $this->assertSame($asset->name, $view->name);
        $this->assertSame($repo->listTaskIds($asset->id), $view->taskIds);
        $this->assertSame($asset->price, $view->price);
        $this->assertSame($asset->type, $view->type);
        $this->assertSame(DateUtils::toAtom($asset->createdAt), $view->createdAt);

        $imageFileIds = $repo->listImageFileIds($asset->id);
        $firstImageFileId = $imageFileIds[0] ?? null;

        if ($firstImageFileId === null) {
            $this->assertNull($view->mainImage);
        } else {
            $this->assertNotNull($view->mainImage);
            $this->assertSame($firstImageFileId, $view->mainImage->id);
            $this->assertStringContainsString($firstImageFileId, $view->mainImage->url);
        }
    }
}
