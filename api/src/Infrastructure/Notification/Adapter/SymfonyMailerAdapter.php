<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Adapter;

use App\Application\Port\Notification\MailerInterface;
use Symfony\Component\Mailer\MailerInterface as SymfonyMailerInterface;
use Symfony\Component\Mime\Email;

final readonly class SymfonyMailerAdapter implements MailerInterface
{
    public function __construct(
        private SymfonyMailerInterface $mailer,
        private string $fromAddress,
    ) {
    }

    public function send(string $to, string $subject, string $body): void
    {
        $email = (new Email())
            ->from($this->fromAddress)
            ->to($to)
            ->subject($subject)
            ->html($body);

        $this->mailer->send($email);
    }
}
