<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Task\Handler;

use App\Application\Event\Task\TaskCreated;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class TaskCreatedMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(TaskCreated $event): void
    {
        $this->logger->info('Task created asynchronously.', [
            'taskId' => $event->taskId,
            'name' => $event->name,
            'estimateTime' => $event->estimateTime,
            'parentId' => $event->parentId,
        ]);
    }
}
