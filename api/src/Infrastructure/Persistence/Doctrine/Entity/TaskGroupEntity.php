<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;
use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: self::TABLE)]
class TaskGroupEntity
{
    public const TABLE = 'task_groups';

    public const COLUMN_ID = 'id';
    public const COLUMN_NAME = 'name';
    public const COLUMN_STATUS = 'status';
    public const COLUMN_CREATED_AT = 'created_at';
    public const COLUMN_OWNER_ID = 'owner_id';

    public const JOIN_TABLE_TASK = 'task_group_task';
    public const JOIN_COLUMN_TASK_GROUP_ID = 'task_group_id';
    public const JOIN_COLUMN_TASK_ID = 'task_id';

    /** Doctrine property names for DQL (not SQL column names). */
    public const FIELD_ID = 'id';
    public const FIELD_NAME = 'name';
    public const FIELD_STATUS = 'status';
    public const FIELD_CREATED_AT = 'createdAt';
    public const FIELD_OWNER = 'owner';
    public const FIELD_OWNER_ID = 'ownerId';
    public const FIELD_TASKS = 'tasks';

    #[ORM\Id]
    #[ORM\Column(name: self::COLUMN_ID, type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: self::COLUMN_OWNER_ID, referencedColumnName: 'id', nullable: false)]
    private UserEntity $owner;

    /** Read-only mirror of owner_id — hydrates without joining users. */
    #[ORM\Column(name: self::COLUMN_OWNER_ID, type: 'uuid', insertable: false, updatable: false)]
    private Uuid $ownerId;

    #[ORM\Column(name: self::COLUMN_NAME, length: TaskGroupModel::NAME_MAX_LENGTH)]
    private string $name = '';

    #[ORM\Column(name: self::COLUMN_STATUS, type: 'string', enumType: TaskGroupStatusEnum::class)]
    private TaskGroupStatusEnum $status = TaskGroupStatusEnum::Initial;

    /** @var Collection<int, TaskEntity> */
    #[ORM\ManyToMany(targetEntity: TaskEntity::class, inversedBy: 'taskGroups')]
    #[ORM\JoinTable(name: self::JOIN_TABLE_TASK)]
    #[ORM\JoinColumn(name: self::JOIN_COLUMN_TASK_GROUP_ID, referencedColumnName: self::COLUMN_ID, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: self::JOIN_COLUMN_TASK_ID, referencedColumnName: TaskEntity::COLUMN_ID, onDelete: 'CASCADE')]
    private Collection $tasks;

    #[ORM\Column(name: self::COLUMN_CREATED_AT)]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = UidUtils::generate();
        $this->createdAt = new DateTimeImmutable();
        $this->tasks = new ArrayCollection();
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

    public function getOwner(): UserEntity
    {
        return $this->owner;
    }

    public function setOwner(UserEntity $owner): self
    {
        $this->owner = $owner;
        $this->ownerId = $owner->getId();

        return $this;
    }

    public function getOwnerId(): Uuid
    {
        return $this->ownerId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStatus(): TaskGroupStatusEnum
    {
        return $this->status;
    }

    public function setStatus(TaskGroupStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
    }

    /** @return Collection<int, TaskEntity> */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
