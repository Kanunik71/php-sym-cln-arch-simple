<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Application\Port\Messaging\MessageBusInterface;
use Symfony\Component\Messenger\MessageBusInterface as SymfonyMessageBusInterface;

/**
 * Adapter of the Application port over Symfony Messenger.
 *
 * Routing messages to transports lives in `config/packages/messenger.yaml`.
 */
final readonly class MessengerMessageBus implements MessageBusInterface
{
    public function __construct(
        private SymfonyMessageBusInterface $messageBus,
    ) {
    }

    public function dispatch(object $message): void
    {
        $this->messageBus->dispatch($message);
    }
}
