<?php

declare(strict_types=1);

namespace App\Application\Model\Task\Action;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Enum\Task\TaskStatusEnum;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class TaskChangeStatusModel implements ArrayableModelInterface
{
    public function __construct(
        public TaskStatusEnum $status,
        public ?string $assignUserId = null,
        public ?int $estimateTime = null,
        public ?string $cancelReason = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            status: InputAssertUtils::requiredEnum($data['status'] ?? null, 'status', TaskStatusEnum::class),
            assignUserId: InputAssertUtils::optionalNonEmptyString($data['userId'] ?? null, 'userId'),
            estimateTime: InputAssertUtils::optionalInt($data['estimateTime'] ?? null, 'estimateTime'),
            cancelReason: InputAssertUtils::optionalNonEmptyString($data['cancelReason'] ?? null, 'cancelReason'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'userId' => $this->assignUserId,
            'estimateTime' => $this->estimateTime,
            'cancelReason' => $this->cancelReason,
        ];
    }
}
