<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Application\Enum\File\FileStatusEnum;
use App\Application\Model\File\FileModel;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'files')]
class FileEntity
{
    /** Doctrine property names for DQL (not SQL column names). */
    public const FIELD_ID = 'id';
    public const FIELD_OWNER = 'owner';
    public const FIELD_OWNER_ID = 'ownerId';
    public const FIELD_STATUS = 'status';
    public const FIELD_CREATED_AT = 'createdAt';
    public const FIELD_UPDATED_AT = 'updatedAt';

    public const COLUMN_OWNER_ID = 'owner_id';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: self::COLUMN_OWNER_ID, referencedColumnName: 'id', nullable: false)]
    private UserEntity $owner;

    /** Read-only mirror of owner_id — hydrates without joining users. */
    #[ORM\Column(name: self::COLUMN_OWNER_ID, type: 'uuid', insertable: false, updatable: false)]
    private Uuid $ownerId;

    #[ORM\Column(name: 'original_name', length: FileModel::ORIGINAL_NAME_MAX_LENGTH)]
    private string $originalName = '';

    #[ORM\Column(name: 'mime_type', length: FileModel::MIME_TYPE_MAX_LENGTH)]
    private string $mimeType = '';

    #[ORM\Column(name: 'size_bytes', type: 'integer')]
    private int $sizeBytes = 0;

    #[ORM\Column(name: 'storage_key', length: FileModel::STORAGE_KEY_MAX_LENGTH)]
    private string $storageKey = '';

    #[ORM\Column(type: 'string', enumType: FileStatusEnum::class)]
    private FileStatusEnum $status = FileStatusEnum::Temporary;

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

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function setSizeBytes(int $sizeBytes): self
    {
        $this->sizeBytes = $sizeBytes;

        return $this;
    }

    public function getStorageKey(): string
    {
        return $this->storageKey;
    }

    public function setStorageKey(string $storageKey): self
    {
        $this->storageKey = $storageKey;

        return $this;
    }

    public function getStatus(): FileStatusEnum
    {
        return $this->status;
    }

    public function setStatus(FileStatusEnum $status): self
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
}
