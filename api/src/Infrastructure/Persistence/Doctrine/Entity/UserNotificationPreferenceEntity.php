<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Shared\Utils\UidUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'user_notification_preferences')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_NOTIFICATION_TYPE', columns: ['user_id', 'type'])]
class UserNotificationPreferenceEntity
{
    /** Doctrine property names for DQL (not SQL column names). */
    public const FIELD_ID = 'id';
    public const FIELD_USER = 'user';
    public const FIELD_USER_ID = 'userId';
    public const FIELD_TYPE = 'type';

    public const COLUMN_USER_ID = 'user_id';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: self::COLUMN_USER_ID, referencedColumnName: 'id', nullable: false)]
    private UserEntity $user;

    /** Read-only mirror of user_id — hydrates without joining users. */
    #[ORM\Column(name: self::COLUMN_USER_ID, type: 'uuid', insertable: false, updatable: false)]
    private Uuid $userId;

    #[ORM\Column(length: NotificationTypeEnum::MAX_LENGTH)]
    private string $type = '';

    #[ORM\Column]
    private bool $enabled = true;

    #[ORM\Column(name: 'email_enabled')]
    private bool $emailEnabled = true;

    #[ORM\Column(name: 'sms_enabled')]
    private bool $smsEnabled = true;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isEmailEnabled(): bool
    {
        return $this->emailEnabled;
    }

    public function setEmailEnabled(bool $emailEnabled): self
    {
        $this->emailEnabled = $emailEnabled;

        return $this;
    }

    public function isSmsEnabled(): bool
    {
        return $this->smsEnabled;
    }

    public function setSmsEnabled(bool $smsEnabled): self
    {
        $this->smsEnabled = $smsEnabled;

        return $this;
    }
}
