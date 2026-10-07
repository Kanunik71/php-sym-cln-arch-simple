<?php

declare(strict_types=1);

namespace App\Application\Service\Task\Lifecycle;

use App\Application\Cache\Task\TaskCache;
use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Event\Task\TaskCreated;
use App\Application\Model\Task\TaskModel;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class TaskLifecycleService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private TaskCache $taskCache,
        private TaskGroupRecalculationDispatcher $recalculationDispatcher,
    ) {
    }

    /**
     * @param list<string> $previousUserIds user ids linked before update; ignored on create
     * @param list<string> $currentUserIds  user ids linked after update; ignored on create
     */
    public function afterTaskPersisted(
        TaskModel $task,
        string $actorUserId,
        bool $created = false,
        array $previousUserIds = [],
        array $currentUserIds = [],
    ): void {
        if ($created) {
            $this->messageBus->dispatch(new TaskCreated(
                taskId: $task->id,
                name: $task->name,
                estimateTime: $task->estimateTime,
                parentId: $task->parentId,
            ));
            $this->taskCache->invalidateUserList($actorUserId);

            return;
        }

        $this->taskCache->invalidateUserLists(
            $actorUserId,
            ...$previousUserIds,
            ...$currentUserIds,
        );

        $this->recalculationDispatcher->requestByTaskId($task->id);
    }
}
