<?php

declare(strict_types=1);

namespace App\Application\Model\TaskGroup\Action;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class TaskGroupCreateModel implements ArrayableModelInterface
{
    /**
     * @param list<string> $taskIds
     */
    public function __construct(
        public string $name,
        public array $taskIds = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            name: InputAssertUtils::requiredString($data['name'] ?? null, 'name'),
            taskIds: InputAssertUtils::optionalUuidList($data['taskIds'] ?? null, 'taskIds') ?? [],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'taskIds' => $this->taskIds,
        ];
    }
}
