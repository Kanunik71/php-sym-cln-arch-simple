<?php

declare(strict_types=1);

namespace App\Application\Enum\Notification;

enum NotificationChannelEnum: string
{
    case Email = 'email';
    case Sms = 'sms';
}
