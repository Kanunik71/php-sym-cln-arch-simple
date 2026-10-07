<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Adapter;

use App\Application\Port\Notification\SmsSenderInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingSmsSenderAdapter implements SmsSenderInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function send(string $to, string $body): void
    {
        $this->logger->info('SMS notification (stub).', [
            'to' => $to,
            'body' => $body,
        ]);
    }
}
