<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Application\Enum\Task\TaskUserStatusEnum;
use App\Shared\Utils\UidUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'task_users')]
#[ORM\UniqueConstraint(name: 'UNIQ_TASK_USER', columns: ['task_id', 'user_id'])]
class TaskUserEntity
{
    /** Doctrine property names for DQL (not SQL column names). */
    public const FIELD_ID = 'id';
    public const FIELD_TASK = 'task';
    public const FIELD_USER = 'user';
    public const FIELD_USER_ID = 'userId';

    public const COLUMN_USER_ID = 'user_id';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: TaskEntity::class, inversedBy: 'taskUsers')]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', nullable: false)]
    private TaskEntity $task;

    #[ORM\ManyToOne(targetEntity: UserEntity::class, inversedBy: 'taskUsers')]
    #[ORM\JoinColumn(name: self::COLUMN_USER_ID, referencedColumnName: 'id', nullable: false)]
    private UserEntity $user;

    /** Read-only mirror of user_id — hydrates without joining users. */
    #[ORM\Column(name: self::COLUMN_USER_ID, type: 'uuid', insertable: false, updatable: false)]
    private Uuid $userId;

    #[ORM\Column(type: 'string', enumType: TaskUserStatusEnum::class)]
    private TaskUserStatusEnum $status = TaskUserStatusEnum::Initial;

    public function __construct()
    {
        $this->id = UidUtils::generate();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getTask(): TaskEntity
    {
        return $this->task;
    }

    public function setTask(TaskEntity $task): self
    {
        $this->task = $task;

        return $this;
    }

    public function getUser(): UserEntity
    {
        return $this->user;
    }

    public function setUser(UserEntity $user): self
    {
        $this->user = $user;
        $this->userId = $user->getId();

        return $this;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getStatus(): TaskUserStatusEnum
    {
        return $this->status;
    }

    public function setStatus(TaskUserStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
    }
}
