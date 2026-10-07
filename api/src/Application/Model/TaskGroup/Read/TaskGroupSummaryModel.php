<?php

declare(strict_types=1);

namespace App\Application\Model\TaskGroup\Read;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class TaskGroupSummaryModel implements ArrayableModelInterface
{
    public function __construct(
        public string $id,
        public string $name,
        public TaskStatusCountsModel $taskStatusCounts,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: InputAssertUtils::requiredString($data['id'] ?? null, 'id'),
            name: InputAssertUtils::requiredString($data['name'] ?? null, 'name'),
            taskStatusCounts: InputAssertUtils::requiredNestedModel(
                $data['taskStatusCounts'] ?? null,
                'taskStatusCounts',
                static fn (array $item): TaskStatusCountsModel => TaskStatusCountsModel::fromArray($item),
            ),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'taskStatusCounts' => $this->taskStatusCounts->toArray(),
        ];
    }
}
