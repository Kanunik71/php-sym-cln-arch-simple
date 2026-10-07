<?php

declare(strict_types=1);

namespace App\Application\Model\TaskGroup\Read;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Enum\Task\TaskStatusEnum;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class TaskStatusCountsModel implements ArrayableModelInterface
{
    public function __construct(
        public int $initial = 0,
        public int $active = 0,
        public int $canceled = 0,
        public int $finished = 0,
    ) {
    }

    public static function empty(): self
    {
        return new self();
    }

    public function withCount(TaskStatusEnum $status, int $count): self
    {
        return match ($status) {
            TaskStatusEnum::Initial => new self(
                initial: $count,
                active: $this->active,
                canceled: $this->canceled,
                finished: $this->finished,
            ),
            TaskStatusEnum::Active => new self(
                initial: $this->initial,
                active: $count,
                canceled: $this->canceled,
                finished: $this->finished,
            ),
            TaskStatusEnum::Canceled => new self(
                initial: $this->initial,
                active: $this->active,
                canceled: $count,
                finished: $this->finished,
            ),
            TaskStatusEnum::Finished => new self(
                initial: $this->initial,
                active: $this->active,
                canceled: $this->canceled,
                finished: $count,
            ),
        };
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            initial: InputAssertUtils::requiredInt($data['initial'] ?? null, 'initial'),
            active: InputAssertUtils::requiredInt($data['active'] ?? null, 'active'),
            canceled: InputAssertUtils::requiredInt($data['canceled'] ?? null, 'canceled'),
            finished: InputAssertUtils::requiredInt($data['finished'] ?? null, 'finished'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'initial' => $this->initial,
            'active' => $this->active,
            'canceled' => $this->canceled,
            'finished' => $this->finished,
        ];
    }
}
