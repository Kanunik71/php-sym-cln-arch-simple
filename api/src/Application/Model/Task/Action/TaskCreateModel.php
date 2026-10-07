<?php

declare(strict_types=1);

namespace App\Application\Model\Task\Action;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class TaskCreateModel implements ArrayableModelInterface
{
    public function __construct(
        public string $name,
        public ?int $estimateTime = null,
        public ?string $parentId = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            name: InputAssertUtils::requiredString($data['name'] ?? null, 'name'),
            estimateTime: InputAssertUtils::optionalInt($data['estimateTime'] ?? null, 'estimateTime'),
            parentId: InputAssertUtils::optionalUuid($data['parentId'] ?? null, 'parentId'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'estimateTime' => $this->estimateTime,
            'parentId' => $this->parentId,
        ];
    }
}
