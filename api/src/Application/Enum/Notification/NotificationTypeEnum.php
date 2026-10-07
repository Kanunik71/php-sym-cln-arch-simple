<?php

declare(strict_types=1);

namespace App\Application\Enum\Notification;

enum NotificationTypeEnum: string
{
    /** Persisted column max length (user_notification_preferences.type). */
    public const MAX_LENGTH = 50;

    case UserRegistered = 'user_registered';
    case UserUpdated = 'user_updated';

    public function isTransactional(): bool
    {
        return $this === self::UserRegistered;
    }

    /** @return list<self> */
    public static function optInCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $type): bool => !$type->isTransactional(),
        ));
    }
}
