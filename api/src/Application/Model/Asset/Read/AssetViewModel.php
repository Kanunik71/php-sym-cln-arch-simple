<?php

declare(strict_types=1);

namespace App\Application\Model\Asset\Read;

use App\Application\Enum\Asset\AssetTypeEnum;
use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Model\Common\FileItemModel;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class AssetViewModel implements ArrayableModelInterface
{
    /**
     * @param list<string> $taskIds
     */
    public function __construct(
        public string $id,
        public string $ownerId,
        public string $name,
        public array $taskIds,
        public float $price,
        public AssetTypeEnum $type,
        public string $createdAt,
        public bool $inProgress,
        public ?FileItemModel $mainImage = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: InputAssertUtils::requiredString($data['id'] ?? null, 'id'),
            ownerId: InputAssertUtils::requiredString($data['ownerId'] ?? null, 'ownerId'),
            name: InputAssertUtils::requiredString($data['name'] ?? null, 'name'),
            taskIds: InputAssertUtils::optionalUuidList($data['taskIds'] ?? null, 'taskIds') ?? [],
            price: InputAssertUtils::requiredFloat($data['price'] ?? null, 'price'),
            type: InputAssertUtils::requiredEnum($data['type'] ?? null, 'type', AssetTypeEnum::class),
            createdAt: InputAssertUtils::requiredString($data['createdAt'] ?? null, 'createdAt'),
            inProgress: InputAssertUtils::requiredBool($data['inProgress'] ?? null, 'inProgress'),
            mainImage: InputAssertUtils::optionalNestedModel(
                $data['mainImage'] ?? null,
                'mainImage',
                FileItemModel::fromArray(...),
            ),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ownerId' => $this->ownerId,
            'name' => $this->name,
            'taskIds' => $this->taskIds,
            'price' => $this->price,
            'type' => $this->type->value,
            'createdAt' => $this->createdAt,
            'inProgress' => $this->inProgress,
            'mainImage' => $this->mainImage?->toArray(),
        ];
    }
}
