<?php

declare(strict_types=1);

namespace App\Application\Model\User;

use App\Application\Utils\Wire\StringAssertUtils;
use App\Application\ValueObject\Common\EmailVo;
use App\Application\ValueObject\Common\PhoneVo;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;

/**
 * Persistence model — mirrors users table columns.
 *
 * Fields with an invariant are Value Objects (EmailVo, PhoneVo); the rest are scalars.
 */
final readonly class UserModel
{
    /** Persisted column max length (users.fname). */
    public const FNAME_MAX_LENGTH = 100;

    /** Persisted column max length (users.lname). */
    public const LNAME_MAX_LENGTH = 100;

    /** Persisted column max length (users.password). */
    public const PASSWORD_HASH_MAX_LENGTH = 255;

    /** Persisted column max length (users.city). */
    public const CITY_MAX_LENGTH = 100;

    public ?string $fname;
    public ?string $lname;
    public string $passwordHash;
    public ?string $city;

    public function __construct(
        public string $id,
        ?string $fname,
        ?string $lname,
        public EmailVo $email,
        string $passwordHash,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        ?string $city = null,
        public ?string $avatarFileId = null,
        public ?PhoneVo $phone = null,
    ) {
        $this->fname = StringAssertUtils::maxLengthOrNull(
            StringAssertUtils::nullIfBlank($fname),
            self::FNAME_MAX_LENGTH,
            'First name',
        );
        $this->lname = StringAssertUtils::maxLengthOrNull(
            StringAssertUtils::nullIfBlank($lname),
            self::LNAME_MAX_LENGTH,
            'Last name',
        );
        $this->passwordHash = StringAssertUtils::notBlankMaxLength(
            $passwordHash,
            self::PASSWORD_HASH_MAX_LENGTH,
            'Password hash',
        );
        $this->city = StringAssertUtils::maxLengthOrNull(
            StringAssertUtils::nullIfBlank($city),
            self::CITY_MAX_LENGTH,
            'City',
        );
    }

    public static function create(
        EmailVo $email,
        string $passwordHash,
        ?string $fname = null,
        ?string $lname = null,
        ?string $city = null,
        ?PhoneVo $phone = null,
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            id: UidUtils::generateString(),
            fname: $fname,
            lname: $lname,
            email: $email,
            passwordHash: $passwordHash,
            createdAt: $now,
            updatedAt: $now,
            city: $city,
            phone: $phone,
        );
    }

    /** @return list<string> */
    public function getAllFileIds(): array
    {
        return $this->avatarFileId !== null ? [$this->avatarFileId] : [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFname(): ?string
    {
        return $this->fname;
    }

    public function getLname(): ?string
    {
        return $this->lname;
    }

    public function getEmail(): EmailVo
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getAvatarFileId(): ?string
    {
        return $this->avatarFileId;
    }

    public function getPhone(): ?PhoneVo
    {
        return $this->phone;
    }

    public function withProfile(
        ?string $fname,
        ?string $lname,
        EmailVo $email,
        string $passwordHash,
        ?string $city,
        ?string $avatarFileId,
        ?PhoneVo $phone = null,
    ): self {
        return new self(
            id: $this->id,
            fname: $fname,
            lname: $lname,
            email: $email,
            passwordHash: $passwordHash,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            city: $city,
            avatarFileId: $avatarFileId,
            phone: $phone,
        );
    }
}
