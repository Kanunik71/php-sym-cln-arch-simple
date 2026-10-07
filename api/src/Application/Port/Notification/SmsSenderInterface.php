<?php

declare(strict_types=1);

namespace App\Application\Port\Notification;

interface SmsSenderInterface
{
    public function send(string $to, string $body): void;
}
