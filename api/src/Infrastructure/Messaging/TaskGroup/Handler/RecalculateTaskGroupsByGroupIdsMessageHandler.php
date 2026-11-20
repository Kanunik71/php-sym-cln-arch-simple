<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\TaskGroup\Handler;

use App\Application\Event\TaskGroup\TaskGroupRecalculationByGroupIdsRequestedEvent;
use App\Application\Port\TaskGroup\TaskGroupLockInterface;
use App\Application\Service\TaskGroup\Lifecycle\TaskGroupRecalculationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Пересчёт указанных групп.
 *
 * Одновременно считается только один канал: лок берёт потребитель, а не
 * продюсер (HTTP-запрос не может удерживать лок до конца async-работы).
 * Если лок занят, запрос пропускается — пересчёт уже идёт.
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
