<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\TaskGroup\Handler;

use App\Application\Event\TaskGroup\TaskGroupRecalculationByTaskRequestedEvent;
use App\Application\Port\TaskGroup\TaskGroupLockInterface;
use App\Application\Service\TaskGroup\Lifecycle\TaskGroupRecalculationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Recalculates the groups of a task.
 *
 * Only one channel is processed at a time: the consumer takes the lock, not the
 * producer (an HTTP request cannot hold the lock until the async work finishes).
 * If the lock is held, the message is skipped — a recalculation is already running.
 */
#[AsMessageHandler]
final readonly class RecalculateTaskGroupsByTaskMessageHandler
{
    public function __construct(
        private TaskGroupRecalculationService $recalculationService,
        private TaskGroupLockInterface $taskGroupLock,
    ) {
    }

    public function __invoke(TaskGroupRecalculationByTaskRequestedEvent $event): void
    {
        $handle = $this->taskGroupLock->acquireByTaskId($event->taskId);

        if ($handle === null) {
            return;
        }

        $refresh = function () use ($handle): void {
            $handle->refresh();
        };

        try {
            $this->recalculationService->recalculateByTaskId($event->taskId, $refresh);
        } finally {
            $handle->release();
        }
    }
}
