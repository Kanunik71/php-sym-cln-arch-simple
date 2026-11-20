<?php

declare(strict_types=1);

namespace App\Application\Service\Task\Lifecycle;

use App\Application\Event\Task\TaskCreatedEvent;
use App\Application\Event\TaskGroup\TaskGroupRecalculationByTaskRequestedEvent;
use App\Application\Model\Task\TaskModel;
use App\Application\Port\Messaging\MessageBusInterface;

final readonly class TaskLifecycleService
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function afterTaskPersisted(TaskModel $task, bool $created = false): void
    {
        if ($created) {
            $this->messageBus->dispatch(new TaskCreatedEvent(
                taskId: $task->id,
                name: $task->name,
                estimateTime: $task->estimateTime,
                parentId: $task->parentId,
            ));

            return;
        }

        $this->messageBus->dispatch(new TaskGroupRecalculationByTaskRequestedEvent(taskId: $task->id));
    }
}
