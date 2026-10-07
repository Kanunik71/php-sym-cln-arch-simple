<?php

declare(strict_types=1);

namespace App\Application\Dispatcher\TaskGroup;

use App\Application\Cache\TaskGroup\TaskGroupCache;
use App\Application\Event\TaskGroup\TaskGroupsRecalculationRequestedEvent;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class TaskGroupRecalculationDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private TaskGroupCache $taskGroupCache,
    ) {
    }

    public function requestByTaskId(string $taskId): void
    {
        if (!$this->taskGroupCache->tryAcquireTaskLock($taskId)) {
            return;
        }

        $this->messageBus->dispatch(new TaskGroupsRecalculationRequestedEvent(taskId: $taskId));
    }

    /**
     * @param list<string> $taskGroupIds
     */
    public function requestByGroupIds(array $taskGroupIds): void
    {
        if ($taskGroupIds === []) {
            return;
        }

        $uniqueGroupIds = array_values(array_unique($taskGroupIds));
        sort($uniqueGroupIds);

        if (!$this->taskGroupCache->tryAcquireGroupIdsLock($uniqueGroupIds)) {
            return;
        }

        $this->messageBus->dispatch(new TaskGroupsRecalculationRequestedEvent(taskGroupIds: $uniqueGroupIds));
    }

    public function releaseTaskLock(string $taskId): void
    {
        $this->taskGroupCache->releaseTaskLock($taskId);
    }

    /**
     * @param list<string> $taskGroupIds
     */
    public function releaseGroupIdsLock(array $taskGroupIds): void
    {
        $this->taskGroupCache->releaseGroupIdsLock($taskGroupIds);
    }
}
