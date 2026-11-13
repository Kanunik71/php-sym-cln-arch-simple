<?php

declare(strict_types=1);

namespace App\Application\Port\Notification;

use App\Application\Enum\Notification\NotificationChannelEnum;
use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Application\Model\Notification\RenderedNotificationModel;

interface NotificationTemplateRendererInterface
{
    /**
     * @param array<string, mixed> $vars
     */
    public function render(
        NotificationTypeEnum $type,
        NotificationChannelEnum $channel,
        array $vars,
    ): RenderedNotificationModel;
}
