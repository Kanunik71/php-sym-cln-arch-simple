<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Application\Enum\Asset\AssetTypeEnum;
use App\Application\Model\Asset\AssetModel;
use App\Shared\Utils\MoneyUtils;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'assets')]
class AssetEntity
{
    public const COLUMN_ID = 'id';
    public const COLUMN_OWNER_ID = 'owner_id';

    public const JOIN_TABLE_TASK = 'asset_task';
    public const JOIN_COLUMN_ASSET_ID = 'asset_id';
    public const JOIN_COLUMN_TASK_ID = 'task_id';

    /** Doctrine property names for DQL (not SQL column names). */
    public const FIELD_ID = 'id';
    public const FIELD_OWNER = 'owner';
    public const FIELD_OWNER_ID = 'ownerId';
    public const FIELD_TASKS = 'tasks';
    public const FIELD_ASSET_FILES = 'assetFiles';
    public const FIELD_CREATED_AT = 'createdAt';
    public const FIELD_UPDATED_AT = 'updatedAt';

    #[ORM\Id]
    #[ORM\Column(name: self::COLUMN_ID, type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: AssetModel::NAME_MAX_LENGTH)]
    private string $name = '';

    #[ORM\Column(type: 'decimal', precision: MoneyUtils::PRECISION, scale: MoneyUtils::SCALE)]
    private string $price = '0.00';

    #[ORM\Column(type: 'string', enumType: AssetTypeEnum::class)]
    private AssetTypeEnum $type = AssetTypeEnum::Virtual;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: self::COLUMN_OWNER_ID, referencedColumnName: 'id', nullable: false)]
    private UserEntity $owner;

    /** Read-only mirror of owner_id — hydrates without joining users. */
    #[ORM\Column(name: self::COLUMN_OWNER_ID, type: 'uuid', insertable: false, updatable: false)]
    private Uuid $ownerId;

    /** @var Collection<int, TaskEntity> */
    #[ORM\ManyToMany(targetEntity: TaskEntity::class, inversedBy: 'assets')]
    #[ORM\JoinTable(name: self::JOIN_TABLE_TASK)]
    #[ORM\JoinColumn(name: self::JOIN_COLUMN_ASSET_ID, referencedColumnName: self::COLUMN_ID, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: self::JOIN_COLUMN_TASK_ID, referencedColumnName: TaskEntity::COLUMN_ID, onDelete: 'CASCADE')]
    private Collection $tasks;

    /** @var Collection<int, AssetFileEntity> */
    #[ORM\OneToMany(mappedBy: 'asset', targetEntity: AssetFileEntity::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $assetFiles;

    #[ORM\Column(name: 'created_at')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at')]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = UidUtils::generate();
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->tasks = new ArrayCollection();
        $this->assetFiles = new ArrayCollection();
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getType(): AssetTypeEnum
    {
        return $this->type;
    }

    public function setType(AssetTypeEnum $type): self
    {
        $this->type = $type;

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

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /** @return Collection<int, AssetFileEntity> */
    public function getAssetFiles(): Collection
    {
        return $this->assetFiles;
    }
}
