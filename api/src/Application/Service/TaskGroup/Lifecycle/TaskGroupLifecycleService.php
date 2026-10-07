<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Lifecycle;

use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;
use App\Application\Event\TaskGroup\TaskGroupStatusChangedEvent;
use App\Application\Model\TaskGroup\TaskGroupModel;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class TaskGroupLifecycleService
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function afterStatusChanged(
        TaskGroupModel $taskGroup,
        TaskGroupStatusEnum $previousStatus,
    ): void {
        $this->messageBus->dispatch(new TaskGroupStatusChangedEvent(
            taskGroupId: $taskGroup->id,
            status: $taskGroup->status,
            previousStatus: $previousStatus,
        ));
    }

    public function afterCreated(TaskGroupModel $taskGroup): void
    {
        $this->messageBus->dispatch(new TaskGroupStatusChangedEvent(
            taskGroupId: $taskGroup->id,
            status: $taskGroup->status,
            previousStatus: null,
        ));
    }
}
