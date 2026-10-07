<?php

declare(strict_types=1);

namespace App\Application\Model\Notification;

use App\Application\Enum\Notification\NotificationChannelEnum;
use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Shared\Utils\UidUtils;
use InvalidArgumentException;

/**
 * Persistence model — mirrors user_notification_preferences columns (scalars only).
 */
final readonly class NotificationPreferenceModel
{
    public function __construct(
        public string $id,
        public string $ownerId,
        public NotificationTypeEnum $type,
        public bool $enabled,
        public bool $emailEnabled,
        public bool $smsEnabled,
    ) {
        if ($type->isTransactional()) {
            throw new InvalidArgumentException('Transactional notification types cannot have preferences.');
        }
    }

    public static function createDefaultUserUpdated(string $ownerId): self
    {
        return new self(
            id: UidUtils::generateString(),
            ownerId: $ownerId,
            type: NotificationTypeEnum::UserUpdated,
            enabled: true,
            emailEnabled: true,
            smsEnabled: true,
        );
    }

    /** @return list<NotificationChannelEnum> */
    public function getChannels(): array
    {
        $channels = [];

        if ($this->emailEnabled) {
            $channels[] = NotificationChannelEnum::Email;
        }

        if ($this->smsEnabled) {
            $channels[] = NotificationChannelEnum::Sms;
        }

        return $channels;
    }

    /**
     * @param list<NotificationChannelEnum> $channels
     */
    public function withChannels(bool $enabled, array $channels): self
    {
        $emailEnabled = false;
        $smsEnabled = false;

        foreach ($channels as $channel) {
            match ($channel) {
                NotificationChannelEnum::Email => $emailEnabled = true,
                NotificationChannelEnum::Sms => $smsEnabled = true,
            };
        }

        return new self(
            id: $this->id,
            ownerId: $this->ownerId,
            type: $this->type,
            enabled: $enabled,
            emailEnabled: $emailEnabled,
            smsEnabled: $smsEnabled,
        );
    }

    // Compatibility accessors used across handlers/tests.
    public function getId(): string
    {
        return $this->id;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function getType(): NotificationTypeEnum
    {
        return $this->type;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isEmailEnabled(): bool
    {
        return $this->emailEnabled;
    }

    public function isSmsEnabled(): bool
    {
        return $this->smsEnabled;
    }

    /**
     * @param list<NotificationChannelEnum> $channels
     */
    public function update(bool $enabled, array $channels): self
    {
        return $this->withChannels($enabled, $channels);
    }
}
