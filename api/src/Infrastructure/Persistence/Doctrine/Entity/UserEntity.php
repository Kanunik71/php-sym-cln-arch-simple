<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Application\ValueObject\Common\Email;
use App\Application\ValueObject\Common\Phone;
use App\Application\Model\User\UserModel;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class UserEntity
{
    /** Doctrine property names for DQL (not SQL column names). */
    public const FIELD_ID = 'id';
    public const FIELD_FNAME = 'fname';
    public const FIELD_LNAME = 'lname';
    public const FIELD_EMAIL = 'email';
    public const FIELD_PASSWORD = 'password';
    public const FIELD_CITY = 'city';
    public const FIELD_CREATED_AT = 'createdAt';
    public const FIELD_UPDATED_AT = 'updatedAt';
    public const FIELD_AVATAR_FILE = 'avatarFile';
    public const FIELD_AVATAR_FILE_ID = 'avatarFileId';
    public const FIELD_TASK_USERS = 'taskUsers';

    public const COLUMN_AVATAR_FILE_ID = 'avatar_file_id';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: UserModel::FNAME_MAX_LENGTH, nullable: true)]
    private ?string $fname = null;

    #[ORM\Column(length: UserModel::LNAME_MAX_LENGTH, nullable: true)]
    private ?string $lname = null;

    #[ORM\Column(length: Email::MAX_LENGTH, unique: true)]
    private string $email = '';

    #[ORM\Column(length: UserModel::PASSWORD_HASH_MAX_LENGTH)]
    private string $password = '';

    #[ORM\Column(length: UserModel::CITY_MAX_LENGTH, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: Phone::MAX_LENGTH, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(name: 'created_at')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at')]
    private DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(targetEntity: FileEntity::class)]
    #[ORM\JoinColumn(name: self::COLUMN_AVATAR_FILE_ID, referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?FileEntity $avatarFile = null;

    /** Read-only mirror of avatar_file_id — hydrates without joining files. */
    #[ORM\Column(name: self::COLUMN_AVATAR_FILE_ID, type: 'uuid', nullable: true, insertable: false, updatable: false)]
    private ?Uuid $avatarFileId = null;

    /** @var Collection<int, TaskUserEntity> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: TaskUserEntity::class)]
    private Collection $taskUsers;

    public function __construct()
    {
        $this->id = UidUtils::generate();
        $now = new DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->taskUsers = new ArrayCollection();
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

    public function getFname(): ?string
    {
        return $this->fname;
    }

    public function setFname(?string $fname): self
    {
        $this->fname = $fname;

        return $this;
    }

    public function getLname(): ?string
    {
        return $this->lname;
    }

    public function setLname(?string $lname): self
    {
        $this->lname = $lname;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

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

    public function getAvatarFile(): ?FileEntity
    {
        return $this->avatarFile;
    }

    public function setAvatarFile(?FileEntity $avatarFile): self
    {
        $this->avatarFile = $avatarFile;
        $this->avatarFileId = $avatarFile?->getId();

        return $this;
    }

    public function getAvatarFileId(): ?Uuid
    {
        return $this->avatarFileId;
    }

    /** @return Collection<int, TaskUserEntity> */
    public function getTaskUsers(): Collection
    {
        return $this->taskUsers;
    }
}
