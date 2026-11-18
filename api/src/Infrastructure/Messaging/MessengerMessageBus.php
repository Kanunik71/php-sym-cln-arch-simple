<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Application\Port\Messaging\MessageBusInterface;
use Symfony\Component\Messenger\MessageBusInterface as SymfonyMessageBusInterface;

/**
 * Адаптер Application-порта на Symfony Messenger.
 *
 * Маршрутизация сообщений по транспортам — в `config/packages/messenger.yaml`.
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
