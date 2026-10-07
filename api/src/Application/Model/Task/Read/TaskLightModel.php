<?php

declare(strict_types=1);

namespace App\Application\Model\Task\Read;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Enum\Task\TaskStatusEnum;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class TaskLightModel implements ArrayableModelInterface
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $parentId,
        public ?int $estimateTime,
        public TaskStatusEnum $status,
        public string $createdAt,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: InputAssertUtils::requiredString($data['id'] ?? null, 'id'),
            name: InputAssertUtils::requiredString($data['name'] ?? null, 'name'),
            parentId: InputAssertUtils::optionalString($data['parentId'] ?? null, 'parentId'),
            estimateTime: InputAssertUtils::optionalInt($data['estimateTime'] ?? null, 'estimateTime'),
            status: InputAssertUtils::requiredEnum($data['status'] ?? null, 'status', TaskStatusEnum::class),
            createdAt: InputAssertUtils::requiredString($data['createdAt'] ?? null, 'createdAt'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'parentId' => $this->parentId,
            'estimateTime' => $this->estimateTime,
            'status' => $this->status->value,
            'createdAt' => $this->createdAt,
        ];
    }
}
