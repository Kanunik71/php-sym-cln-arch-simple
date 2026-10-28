<?php

declare(strict_types=1);

namespace App\Application\Service\Task\Lifecycle;

use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Event\Task\TaskCreated;
use App\Application\Model\Task\TaskModel;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class TaskLifecycleService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private TaskGroupRecalculationDispatcher $recalculationDispatcher,
    ) {
    }

    public function afterTaskPersisted(TaskModel $task, bool $created = false): void
    {
        if ($created) {
            $this->messageBus->dispatch(new TaskCreated(
                taskId: $task->id,
                name: $task->name,
                estimateTime: $task->estimateTime,
                parentId: $task->parentId,
            ));

            return;
        }

        $this->recalculationDispatcher->requestByTaskId($task->id);
    }
}
