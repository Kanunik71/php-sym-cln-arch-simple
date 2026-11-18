<?php

declare(strict_types=1);

namespace App\Application\Port\Messaging;

/**
 * Шина сообщений: единственная точка входа Application в async.
 *
 * Application не знает о Messenger — реализация адаптера лежит в
 * Infrastructure, контракт здесь.
 */
interface MessageBusInterface
{
    public function dispatch(object $message): void;
}
