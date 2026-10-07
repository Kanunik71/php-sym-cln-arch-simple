<?php

declare(strict_types=1);

namespace App\Application\Service\User\Lifecycle;

use App\Application\Event\User\UserRegisteredEvent;
use App\Application\Event\User\UserUpdatedEvent;
use App\Application\Model\User\UserModel;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class UserLifecycleService
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function afterUserPersisted(UserModel $user, bool $created = false): void
    {
        if ($created) {
            $this->messageBus->dispatch(new UserRegisteredEvent(userId: $user->id));

            return;
        }

        $this->messageBus->dispatch(new UserUpdatedEvent(userId: $user->id));
    }
}
