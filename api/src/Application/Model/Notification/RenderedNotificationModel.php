<?php

declare(strict_types=1);

namespace App\Application\Model\Notification;

final readonly class RenderedNotificationModel
{
    public function __construct(
        public string $body,
        public ?string $subject = null,
    ) {
    }
}
