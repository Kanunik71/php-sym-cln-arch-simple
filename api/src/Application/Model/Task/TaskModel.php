<?php

declare(strict_types=1);

namespace App\Application\Model\Task;

use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Exception\Task\InvalidTaskStatusTransitionException;
use App\Shared\Utils\Asserts\NumberAssertUtils;
use App\Shared\Utils\Asserts\StringAssertUtils;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Persistence model — mirrors tasks table columns (scalars only).
 * task_users links are passed separately to the repository on save.
 */
final readonly class TaskModel implements \App\Application\Model\Common\IdentifiableInterface
{
    /** Persisted column max length (tasks.name). */
    public const NAME_MAX_LENGTH = 255;

    /** Persisted column max length (tasks.cancel_reason). */
    public const CANCEL_REASON_MAX_LENGTH = 1000;

    public string $name;
    public ?string $parentId;
    public ?int $estimateTime;
    public ?string $cancelReason;

    public function __construct(
        public string $id,
        string $name,
        ?string $parentId,
        ?int $estimateTime,
        public TaskStatusEnum $status,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        ?string $cancelReason = null,
        public ?DateTimeImmutable $finishedDate = null,
        public ?DateTimeImmutable $cancellationDate = null,
    ) {
        $this->name = StringAssertUtils::notBlankMaxLength($name, self::NAME_MAX_LENGTH, 'Task name');
        $this->parentId = StringAssertUtils::nullIfBlank($parentId);
        $this->estimateTime = NumberAssertUtils::minOrNull($estimateTime, 0, 'Estimate time');
        $this->cancelReason = $cancelReason === null
            ? null
            : StringAssertUtils::notBlankMaxLength($cancelReason, self::CANCEL_REASON_MAX_LENGTH, 'Cancel reason');

        if ($this->parentId === $id) {
            throw new InvalidArgumentException('Task cannot be its own parent.');
        }
    }

    public static function create(
        string $name,
        ?int $estimateTime = null,
        ?string $parentId = null,
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            id: UidUtils::generateString(),
            name: $name,
            parentId: $parentId,
            estimateTime: $estimateTime,
            status: TaskStatusEnum::Initial,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function activated(int $estimateTime): self
    {
        $this->assertCanTransitionTo(TaskStatusEnum::Active);

        return new self(
            id: $this->id,
            name: $this->name,
            parentId: $this->parentId,
            estimateTime: NumberAssertUtils::min($estimateTime, 0, 'Estimate time'),
            status: TaskStatusEnum::Active,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            cancelReason: $this->cancelReason,
            finishedDate: $this->finishedDate,
            cancellationDate: $this->cancellationDate,
        );
    }

    public function canceled(string $cancelReason, ?DateTimeImmutable $cancellationDate = null): self
    {
        $this->assertCanTransitionTo(TaskStatusEnum::Canceled);

        return new self(
            id: $this->id,
            name: $this->name,
            parentId: $this->parentId,
            estimateTime: $this->estimateTime,
            status: TaskStatusEnum::Canceled,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            cancelReason: $cancelReason,
            finishedDate: $this->finishedDate,
            cancellationDate: $cancellationDate ?? new DateTimeImmutable(),
        );
    }

    public function finished(?DateTimeImmutable $finishedDate = null): self
    {
        $this->assertCanTransitionTo(TaskStatusEnum::Finished);

        return new self(
            id: $this->id,
            name: $this->name,
            parentId: $this->parentId,
            estimateTime: $this->estimateTime,
            status: TaskStatusEnum::Finished,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            cancelReason: $this->cancelReason,
            finishedDate: $finishedDate ?? new DateTimeImmutable(),
            cancellationDate: $this->cancellationDate,
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function getEstimateTime(): ?int
    {
        return $this->estimateTime;
    }

    public function getStatus(): TaskStatusEnum
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCancelReason(): ?string
    {
        return $this->cancelReason;
    }

    public function getFinishedDate(): ?DateTimeImmutable
    {
        return $this->finishedDate;
    }

    public function getCancellationDate(): ?DateTimeImmutable
    {
        return $this->cancellationDate;
    }

    private function assertCanTransitionTo(TaskStatusEnum $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw InvalidTaskStatusTransitionException::fromTo($this->status, $newStatus);
        }
    }
}
