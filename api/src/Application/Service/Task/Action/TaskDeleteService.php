<?php

declare(strict_types=1);

namespace App\Application\Service\Task\Action;

use App\Application\Event\TaskGroup\TaskGroupsRecalculationByGroupIdsRequestedEvent;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Port\Messaging\MessageBusInterface;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Shared\Utils\ArrayUtils;

final readonly class TaskDeleteService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private TaskGroupRepositoryInterface $taskGroupRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function execute(string $taskId, string $userId): void
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->taskAccessPolicy->assertOwner($task->ownerId, $userId);

        $affectedGroupIds = ArrayUtils::ids(
            $this->taskGroupRepository->listByTaskId($taskId),
        );

        $this->taskRepository->delete($task);

        if ($affectedGroupIds === []) {
            return;
        }

        $this->messageBus->dispatch(new TaskGroupsRecalculationByGroupIdsRequestedEvent(
            taskGroupIds: $affectedGroupIds,
        ));
    }
}
