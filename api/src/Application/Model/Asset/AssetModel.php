<?php

declare(strict_types=1);

namespace App\Application\Model\Asset;

use App\Application\Enum\Asset\AssetTypeEnum;
use App\Shared\Utils\Asserts\StringAssertUtils;
use App\Shared\Utils\MoneyUtils;
use DateTimeImmutable;

/**
 * Persistence model — mirrors assets table columns (scalars only).
 */
final readonly class AssetModel
{
    /** Persisted column max length (assets.name). */
    public const NAME_MAX_LENGTH = 255;

    public string $name;
    public float $price;

    public function __construct(
        public string $id,
        string $name,
        float $price,
        public AssetTypeEnum $type,
        public string $ownerId,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
        $this->name = StringAssertUtils::notBlankMaxLength($name, self::NAME_MAX_LENGTH, 'Asset name');
        $this->price = MoneyUtils::normalize($price);
    }

    public function withNameAndPrice(string $name, float $price): self
    {
        return new self(
            id: $this->id,
            name: $name,
            price: $price,
            type: $this->type,
            ownerId: $this->ownerId,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }
}
