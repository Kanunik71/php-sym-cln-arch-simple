<?php

declare(strict_types=1);

namespace App\Application\Model\Task;

use App\Application\Enum\Task\TaskUserStatusEnum;
use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;
use App\Shared\Utils\Asserts\StringAssertUtils;
use InvalidArgumentException;

/**
 * Junction model for task_users (user_id + status; task_id owned by parent save).
 */
final readonly class TaskUserModel implements ArrayableModelInterface
{
    public string $userId;

    public function __construct(
        string $userId,
        public TaskUserStatusEnum $status,
    ) {
        $this->userId = StringAssertUtils::notBlank($userId, 'User id');
    }

    public static function create(string $userId, TaskUserStatusEnum $status = TaskUserStatusEnum::Initial): self
    {
        return new self($userId, $status);
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            userId: InputAssertUtils::requiredString($data['userId'] ?? null, 'userId'),
            status: InputAssertUtils::requiredEnum($data['status'] ?? null, 'status', TaskUserStatusEnum::class),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'status' => $this->status->value,
        ];
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getStatus(): TaskUserStatusEnum
    {
        return $this->status;
    }

    public function withStatus(TaskUserStatusEnum $status): self
    {
        if (!$this->status->canTransitionTo($status)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot change task user status from "%s" to "%s".',
                $this->status->value,
                $status->value,
            ));
        }

        return new self($this->userId, $status);
    }

    public function withForcedStatus(TaskUserStatusEnum $status): self
    {
        return new self($this->userId, $status);
    }
}
