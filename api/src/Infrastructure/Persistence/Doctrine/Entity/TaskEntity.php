<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Model\Task\TaskModel;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: self::TABLE)]
class TaskEntity
{
    public const TABLE = 'tasks';

    public const COLUMN_ID = 'id';
    public const COLUMN_NAME = 'name';
    public const COLUMN_PARENT_ID = 'parent_id';
    public const COLUMN_ESTIMATE_TIME = 'estimate_time';
    public const COLUMN_STATUS = 'status';
    public const COLUMN_CREATED_AT = 'created_at';
    public const COLUMN_UPDATED_AT = 'updated_at';
    public const COLUMN_CANCEL_REASON = 'cancel_reason';
    public const COLUMN_FINISHED_DATE = 'finished_date';
    public const COLUMN_CANCELLATION_DATE = 'cancellation_date';

    /** Doctrine property names for DQL (not SQL column names). */
    public const FIELD_ID = 'id';
    public const FIELD_NAME = 'name';
    public const FIELD_PARENT = 'parent';
    public const FIELD_PARENT_ID = 'parentId';
    public const FIELD_STATUS = 'status';
    public const FIELD_CREATED_AT = 'createdAt';
    public const FIELD_UPDATED_AT = 'updatedAt';
    public const FIELD_TASK_USERS = 'taskUsers';

    #[ORM\Id]
    #[ORM\Column(name: self::COLUMN_ID, type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(name: self::COLUMN_NAME, length: TaskModel::NAME_MAX_LENGTH)]
    private string $name = '';

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: self::COLUMN_PARENT_ID, referencedColumnName: self::COLUMN_ID, nullable: true, onDelete: 'SET NULL')]
    private ?TaskEntity $parent = null;

    /** Read-only mirror of parent_id — hydrates without joining parent task. */
    #[ORM\Column(name: self::COLUMN_PARENT_ID, type: 'uuid', nullable: true, insertable: false, updatable: false)]
    private ?Uuid $parentId = null;

    /** @var Collection<int, TaskEntity> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    #[ORM\Column(name: self::COLUMN_ESTIMATE_TIME, nullable: true)]
    private ?int $estimateTime = null;

    #[ORM\Column(name: self::COLUMN_STATUS, type: 'string', enumType: TaskStatusEnum::class)]
    private TaskStatusEnum $status = TaskStatusEnum::Initial;

    #[ORM\Column(name: self::COLUMN_CREATED_AT)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: self::COLUMN_UPDATED_AT)]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(name: self::COLUMN_CANCEL_REASON, length: TaskModel::CANCEL_REASON_MAX_LENGTH, nullable: true)]
    private ?string $cancelReason = null;

    #[ORM\Column(name: self::COLUMN_FINISHED_DATE, nullable: true)]
    private ?DateTimeImmutable $finishedDate = null;

    #[ORM\Column(name: self::COLUMN_CANCELLATION_DATE, nullable: true)]
    private ?DateTimeImmutable $cancellationDate = null;

    /** @var Collection<int, TaskUserEntity> */
    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskUserEntity::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $taskUsers;

    /** @var Collection<int, AssetEntity> */
    #[ORM\ManyToMany(targetEntity: AssetEntity::class, mappedBy: 'tasks')]
    private Collection $assets;

    /** @var Collection<int, TaskGroupEntity> */
    #[ORM\ManyToMany(targetEntity: TaskGroupEntity::class, mappedBy: 'tasks')]
    private Collection $taskGroups;

    public function __construct()
    {
        $this->id = UidUtils::generate();
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->children = new ArrayCollection();
        $this->taskUsers = new ArrayCollection();
        $this->assets = new ArrayCollection();
        $this->taskGroups = new ArrayCollection();
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

    public function getParent(): ?TaskEntity
    {
        return $this->parent;
    }

    public function setParent(?TaskEntity $parent): self
    {
        $this->parent = $parent;
        $this->parentId = $parent?->getId();

        return $this;
    }

    public function getParentId(): ?Uuid
    {
        return $this->parentId;
    }

    /** @return Collection<int, TaskEntity> */
    public function getChildren(): Collection
    {
        return $this->children;
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

    public function getEstimateTime(): ?int
    {
        return $this->estimateTime;
    }

    public function setEstimateTime(?int $estimateTime): self
    {
        $this->estimateTime = $estimateTime;

        return $this;
    }

    public function getStatus(): TaskStatusEnum
    {
        return $this->status;
    }

    public function setStatus(TaskStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
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

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCancelReason(): ?string
    {
        return $this->cancelReason;
    }

    public function setCancelReason(?string $cancelReason): self
    {
        $this->cancelReason = $cancelReason;

        return $this;
    }

    public function getFinishedDate(): ?DateTimeImmutable
    {
        return $this->finishedDate;
    }

    public function setFinishedDate(?DateTimeImmutable $finishedDate): self
    {
        $this->finishedDate = $finishedDate;

        return $this;
    }

    public function getCancellationDate(): ?DateTimeImmutable
    {
        return $this->cancellationDate;
    }

    public function setCancellationDate(?DateTimeImmutable $cancellationDate): self
    {
        $this->cancellationDate = $cancellationDate;

        return $this;
    }

    /** @return Collection<int, TaskUserEntity> */
    public function getTaskUsers(): Collection
    {
        return $this->taskUsers;
    }

    public function addTaskUser(TaskUserEntity $taskUser): self
    {
        if (!$this->taskUsers->contains($taskUser)) {
            $this->taskUsers->add($taskUser);
            $taskUser->setTask($this);
        }

        return $this;
    }

    public function clearTaskUsers(): self
    {
        $this->taskUsers->clear();

        return $this;
    }

    /** @return Collection<int, AssetEntity> */
    public function getAssets(): Collection
    {
        return $this->assets;
    }

    /** @return Collection<int, TaskGroupEntity> */
    public function getTaskGroups(): Collection
    {
        return $this->taskGroups;
    }
}
