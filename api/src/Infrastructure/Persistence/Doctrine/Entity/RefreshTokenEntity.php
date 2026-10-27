<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Shared\Utils\UidUtils;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\UniqueConstraint(name: 'UNIQ_REFRESH_TOKEN_HASH', columns: ['token_hash'])]
#[ORM\Index(name: 'IDX_REFRESH_TOKEN_USER', columns: ['user_id'])]
#[ORM\Index(name: 'IDX_REFRESH_TOKEN_FAMILY', columns: ['family_id'])]
#[ORM\Index(name: 'IDX_REFRESH_TOKEN_EXPIRES', columns: ['expires_at'])]
class RefreshTokenEntity
{
    /** Doctrine property names for DQL (not SQL column names). */
    public const FIELD_ID = 'id';
    public const FIELD_USER = 'user';
    public const FIELD_USER_ID = 'userId';
    public const FIELD_TOKEN_HASH = 'tokenHash';
    public const FIELD_FAMILY_ID = 'familyId';
    public const FIELD_CREATED_AT = 'createdAt';
    public const FIELD_EXPIRES_AT = 'expiresAt';
    public const FIELD_REVOKED_AT = 'revokedAt';

    public const COLUMN_USER_ID = 'user_id';
    public const COLUMN_TOKEN_HASH = 'token_hash';
    public const COLUMN_FAMILY_ID = 'family_id';
    public const COLUMN_CREATED_AT = 'created_at';
    public const COLUMN_EXPIRES_AT = 'expires_at';
    public const COLUMN_REVOKED_AT = 'revoked_at';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(
        name: self::COLUMN_USER_ID,
        referencedColumnName: 'id',
        nullable: false,
    )]
    private UserEntity $user;

    /** Read-only mirror of user_id — hydrates without joining users. */
    #[ORM\Column(
        name: self::COLUMN_USER_ID,
        type: 'uuid',
        insertable: false,
        updatable: false,
    )]
    private Uuid $userId;

    #[ORM\Column(name: self::COLUMN_TOKEN_HASH, length: 64, unique: true)]
    private string $tokenHash = '';

    #[ORM\Column(name: self::COLUMN_FAMILY_ID, type: 'uuid')]
    private Uuid $familyId;

    #[ORM\Column(name: self::COLUMN_CREATED_AT)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: self::COLUMN_EXPIRES_AT)]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(name: self::COLUMN_REVOKED_AT, nullable: true)]
    private ?DateTimeImmutable $revokedAt = null;

    public function __construct()
    {
        $this->id = UidUtils::generate();
        $this->createdAt = new DateTimeImmutable();
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

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): self
    {
        $this->tokenHash = $tokenHash;

        return $this;
    }

    public function getFamilyId(): Uuid
    {
        return $this->familyId;
    }

    public function setFamilyId(Uuid $familyId): self
    {
        $this->familyId = $familyId;

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

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getRevokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?DateTimeImmutable $revokedAt): self
    {
        $this->revokedAt = $revokedAt;

        return $this;
    }
}
