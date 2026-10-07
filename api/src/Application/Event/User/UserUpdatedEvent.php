<?php

declare(strict_types=1);

namespace App\Application\Event\User;

final readonly class UserUpdatedEvent
{
    public function __construct(
        public string $userId,
    ) {
    }
}
