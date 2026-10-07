<?php

declare(strict_types=1);

namespace App\Application\Port\Notification;

interface MailerInterface
{
    public function send(string $to, string $subject, string $body): void;
}
