<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\TaskGroup\Handler;

use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Service\TaskGroup\Lifecycle\TaskGroupRecalculationService;
use App\Application\Event\TaskGroup\TaskGroupsRecalculationRequestedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RecalculateTaskGroupsMessageHandler
{
    public function __construct(
        private TaskGroupRecalculationService $recalculationService,
        private TaskGroupRecalculationDispatcher $recalculationDispatcher,
    ) {
    }

    public function __invoke(TaskGroupsRecalculationRequestedEvent $event): void
    {
        if ($event->taskGroupIds !== []) {
            $this->recalculationService->recalculateByGroupIds($event->taskGroupIds);
            $this->recalculationDispatcher->releaseGroupIdsLock($event->taskGroupIds);

            return;
        }

        if ($event->taskId !== null) {
            $this->recalculationService->recalculateByTaskId($event->taskId);
            $this->recalculationDispatcher->releaseTaskLock($event->taskId);
        }
    }
}
