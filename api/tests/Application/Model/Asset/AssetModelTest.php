<?php

declare(strict_types=1);

namespace App\Tests\Application\Model\Asset;

use App\Application\Enum\Asset\AssetTypeEnum;
use App\Application\Model\Asset\AssetModel;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AssetModelTest extends TestCase
{
    public function testRejectsNegativePrice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be a non-negative number with up to 2 decimal places.');

        $this->newAsset(price: -1);
    }

    public function testRejectsBlankName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Asset name must not be empty.');

        $this->newAsset(name: '   ');
    }

    public function testRejectsNameExceedingMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Asset name must not exceed %d characters.',
            AssetModel::NAME_MAX_LENGTH,
        ));

        $this->newAsset(name: str_repeat('a', AssetModel::NAME_MAX_LENGTH + 1));
    }

    public function testRejectsPriceExceedingMaxAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must not exceed 99999999.99.');

        $this->newAsset(price: 100_000_000);
    }

    public function testWithNameAndPriceReturnsUpdatedCopy(): void
    {
        $asset = $this->newAsset(name: 'Laptop', price: 100.0);
        $updated = $asset->withNameAndPrice('Desktop', 200.5);

        $this->assertSame('Desktop', $updated->name);
        $this->assertSame(200.5, $updated->price);
        $this->assertSame($asset->id, $updated->id);
        $this->assertSame('Laptop', $asset->name);
    }

    private function newAsset(string $name = 'Laptop', float $price = 100.0): AssetModel
    {
        $now = new DateTimeImmutable();

        return new AssetModel(
            id: UidUtils::generateString(),
            name: $name,
            price: $price,
            type: AssetTypeEnum::Physical,
            ownerId: UidUtils::generateString(),
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
