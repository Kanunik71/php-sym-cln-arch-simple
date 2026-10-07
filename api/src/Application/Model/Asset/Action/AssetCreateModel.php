<?php

declare(strict_types=1);

namespace App\Application\Model\Asset\Action;

use App\Application\Enum\Asset\AssetTypeEnum;
use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class AssetCreateModel implements ArrayableModelInterface
{
    /**
     * @param list<string> $taskIds
     * @param list<string> $imageFileIds
     */
    public function __construct(
        public string $name,
        public float $price,
        public AssetTypeEnum $type,
        public array $taskIds = [],
        public array $imageFileIds = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            name: InputAssertUtils::requiredString($data['name'] ?? null, 'name'),
            price: InputAssertUtils::requiredFloat($data['price'] ?? null, 'price'),
            type: InputAssertUtils::requiredEnum($data['type'] ?? null, 'type', AssetTypeEnum::class),
            taskIds: InputAssertUtils::optionalUuidList($data['taskIds'] ?? null, 'taskIds') ?? [],
            imageFileIds: InputAssertUtils::optionalUuidList($data['imageFileIds'] ?? null, 'imageFileIds') ?? [],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'type' => $this->type->value,
            'taskIds' => $this->taskIds,
            'imageFileIds' => $this->imageFileIds,
        ];
    }
}
