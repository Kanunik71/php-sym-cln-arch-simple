<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\TaskGroup\Handler;

use App\Application\Event\TaskGroup\TaskGroupRecalculationByGroupIdsRequestedEvent;
use App\Application\Port\TaskGroup\TaskGroupLockInterface;
use App\Application\Service\TaskGroup\Lifecycle\TaskGroupRecalculationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Recalculates the requested groups.
 *
 * Only one channel is processed at a time: the consumer takes the lock, not the
 * producer (an HTTP request cannot hold the lock until the async work finishes).
 * If the lock is held, the message is skipped — a recalculation is already running.
 */
#[AsMessageHandler]
final readonly class RecalculateTaskGroupsByGroupIdsMessageHandler
{
    public function __construct(
        private TaskGroupRecalculationService $recalculationService,
        private TaskGroupLockInterface $taskGroupLock,
    ) {
    }

    public function __invoke(TaskGroupRecalculationByGroupIdsRequestedEvent $event): void
    {
        $handle = $this->taskGroupLock->acquireByGroupIds($event->taskGroupIds);

        if ($handle === null) {
            return;
        }

        $refresh = function () use ($handle): void {
            $handle->refresh();
        };

        try {
            $this->recalculationService->recalculateByGroupIds($event->taskGroupIds, $refresh);
        } finally {
            $handle->release();
        }
    }
}
