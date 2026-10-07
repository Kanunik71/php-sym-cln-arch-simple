<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Asset\Action;

use App\Application\Service\Asset\Action\AssetCreateService;
use App\Application\Model\Asset\Action\AssetCreateModel;
use App\Application\Enum\Asset\AssetTypeEnum;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Model\AssertsAssetModelTrait;

final class AssetCreateServiceTest extends DatabaseTestCase
{
    use AssertsAssetModelTrait;

    public function testCreatesAsset(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');
        $create = new AssetCreateModel(
            name: 'Laptop',
            price: 999.99,
            type: AssetTypeEnum::Physical,
        );

        $createAsset = $this->bed->get(AssetCreateService::class);

        $result = $createAsset->execute(
            $create,
            userId: $user->getId(),
            taskId: $task->getId(),
        );

        $this->assertSame($create->name, $result->name);
        $this->assertSame($create->price, $result->price);
        $this->assertSame(AssetTypeEnum::Physical, $result->type);
        $this->assertSame([$task->getId()], $result->taskIds);
        $this->assertTrue($result->inProgress);

        $this->bed->clear();
        $persisted = $this->bed->findAssetById($result->id);
        $this->assertNotNull($persisted);
        $this->assertAssetModelMatches($persisted, $result);
    }

    public function testCreatesStandaloneAssetWithTaskIds(): void
    {
        $user = $this->bed->seedDefaultUser();
        $task = $this->bed->createTask(name: 'Build feature');

        $createAsset = $this->bed->get(AssetCreateService::class);

        $result = $createAsset->execute(
            new AssetCreateModel(
                name: 'Laptop',
                price: 999.99,
                type: AssetTypeEnum::Physical,
                taskIds: [$task->getId()],
            ),
            userId: $user->getId(),
        );

        $this->assertSame([$task->getId()], $result->taskIds);
    }
}
