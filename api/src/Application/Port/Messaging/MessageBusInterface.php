<?php

declare(strict_types=1);

namespace App\Application\Port\Messaging;

/**
 * Message bus: the single entry point from Application into async.
 *
 * Application does not know about Messenger — the adapter implementation lives in
 * Infrastructure, the contract is here.
 */
interface MessageBusInterface
{
    public function dispatch(object $message): void;
}
