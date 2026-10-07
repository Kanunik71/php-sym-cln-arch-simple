<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\User\Lifecycle;

use App\Application\Service\User\Lifecycle\UserLifecycleService;
use App\Application\Event\User\UserRegisteredEvent;
use App\Application\Event\User\UserUpdatedEvent;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\Trait\Common\AssertMessengerTrait;

final class UserLifecycleServiceTest extends DatabaseTestCase
{
    use AssertMessengerTrait;

    public function testDispatchesRegisteredEvent(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $service = $this->bed->get(UserLifecycleService::class);

        $service->afterUserPersisted($user, created: true);

        $this->assertDispatched(
            UserRegisteredEvent::class,
            predicate: static fn (UserRegisteredEvent $event): bool => $event->userId === $user->getId(),
        );
    }

    public function testDispatchesUpdatedEvent(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $service = $this->bed->get(UserLifecycleService::class);

        $service->afterUserPersisted($user, created: false);

        $this->assertDispatched(
            UserUpdatedEvent::class,
            predicate: static fn (UserUpdatedEvent $event): bool => $event->userId === $user->getId(),
        );
    }
}
