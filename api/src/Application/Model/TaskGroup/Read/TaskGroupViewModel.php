<?php

declare(strict_types=1);

namespace App\Application\Model\TaskGroup\Read;

use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;
use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class TaskGroupViewModel implements ArrayableModelInterface
{
    public function __construct(
        public string $id,
        public string $ownerId,
        public string $name,
        public TaskGroupStatusEnum $status,
        public string $createdAt,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: InputAssertUtils::requiredString($data['id'] ?? null, 'id'),
            ownerId: InputAssertUtils::requiredString($data['ownerId'] ?? null, 'ownerId'),
            name: InputAssertUtils::requiredString($data['name'] ?? null, 'name'),
            status: InputAssertUtils::requiredEnum($data['status'] ?? null, 'status', TaskGroupStatusEnum::class),
            createdAt: InputAssertUtils::requiredString($data['createdAt'] ?? null, 'createdAt'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ownerId' => $this->ownerId,
            'name' => $this->name,
            'status' => $this->status->value,
            'createdAt' => $this->createdAt,
        ];
    }
}
