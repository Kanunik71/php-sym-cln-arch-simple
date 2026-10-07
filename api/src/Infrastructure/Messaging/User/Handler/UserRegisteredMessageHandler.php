<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\User\Handler;

use App\Application\Service\Notification\Lifecycle\NotificationService;
use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Application\Event\User\UserRegisteredEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UserRegisteredMessageHandler
{
    public function __construct(
        private NotificationService $notificationService,
    ) {
    }

    public function __invoke(UserRegisteredEvent $event): void
    {
        $this->notificationService->notify($event->userId, NotificationTypeEnum::UserRegistered);
    }
}
