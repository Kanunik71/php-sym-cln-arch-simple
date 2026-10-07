<?php

declare(strict_types=1);

namespace App\Application\Model\Asset\Read;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Model\Common\FileItemModel;
use App\Application\Model\Task\Read\TaskLightModel;
use App\Application\Enum\Asset\AssetTypeEnum;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class AssetDetailModel implements ArrayableModelInterface
{
    /**
     * @param list<TaskLightModel> $tasks
     * @param list<FileItemModel>  $images
     */
    public function __construct(
        public string $id,
        public string $ownerId,
        public string $name,
        public array $tasks,
        public float $price,
        public AssetTypeEnum $type,
        public string $createdAt,
        public bool $inProgress,
        public array $images = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: InputAssertUtils::requiredString($data['id'] ?? null, 'id'),
            ownerId: InputAssertUtils::requiredString($data['ownerId'] ?? null, 'ownerId'),
            name: InputAssertUtils::requiredString($data['name'] ?? null, 'name'),
            tasks: InputAssertUtils::nestedModelList(
                $data['tasks'] ?? null,
                'tasks',
                TaskLightModel::fromArray(...),
            ),
            price: InputAssertUtils::requiredFloat($data['price'] ?? null, 'price'),
            type: InputAssertUtils::requiredEnum($data['type'] ?? null, 'type', AssetTypeEnum::class),
            createdAt: InputAssertUtils::requiredString($data['createdAt'] ?? null, 'createdAt'),
            inProgress: InputAssertUtils::requiredBool($data['inProgress'] ?? null, 'inProgress'),
            images: InputAssertUtils::nestedModelList(
                $data['images'] ?? null,
                'images',
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
            'tasks' => array_map(
                static fn (TaskLightModel $item): array => $item->toArray(),
                $this->tasks,
            ),
            'price' => $this->price,
            'type' => $this->type->value,
            'createdAt' => $this->createdAt,
            'inProgress' => $this->inProgress,
            'images' => array_map(
                static fn (FileItemModel $item): array => $item->toArray(),
                $this->images,
            ),
        ];
    }
}
