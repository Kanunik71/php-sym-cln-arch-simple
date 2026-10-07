<?php

declare(strict_types=1);

namespace App\Application\Model\TaskGroup;

use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;
use App\Shared\Utils\Asserts\StringAssertUtils;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;

/**
 * Persistence model — mirrors task_groups table columns (scalars only).
 */
final readonly class TaskGroupModel implements \App\Application\Model\Common\IdentifiableInterface
{
    /** Persisted column max length (task_groups.name). */
    public const NAME_MAX_LENGTH = 255;

    public string $name;

    public function __construct(
        public string $id,
        public string $ownerId,
        string $name,
        public TaskGroupStatusEnum $status,
        public DateTimeImmutable $createdAt,
    ) {
        $this->name = StringAssertUtils::notBlankMaxLength($name, self::NAME_MAX_LENGTH, 'TaskGroup name');
    }

    public static function create(string $name, string $ownerId, ?TaskGroupStatusEnum $status = null): self
    {
        return new self(
            id: UidUtils::generateString(),
            ownerId: $ownerId,
            name: $name,
            status: $status ?? TaskGroupStatusEnum::Initial,
            createdAt: new DateTimeImmutable(),
        );
    }

    public function withStatus(TaskGroupStatusEnum $status): self
    {
        return new self(
            id: $this->id,
            ownerId: $this->ownerId,
            name: $this->name,
            status: $status,
            createdAt: $this->createdAt,
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): TaskGroupStatusEnum
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
