<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Action;

use App\Application\Event\TaskGroup\TaskGroupsRecalculationByGroupIdsRequestedEvent;
use App\Application\Mapper\TaskGroup\TaskGroupViewModelMapper;
use App\Application\Model\TaskGroup\Read\TaskGroupViewModel;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Policy\TaskGroup\TaskGroupAccessPolicy;
use App\Application\Port\Messaging\MessageBusInterface;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;

final readonly class TaskGroupAttachTaskService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskGroupAccessPolicy $taskGroupAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private TaskGroupRepositoryInterface $taskGroupRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function execute(string $userId, string $taskId, string $taskGroupId): TaskGroupViewModel
    {
        $task = $this->taskRepository->findOrFail($taskId);
        $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);

        $taskGroup = $this->taskGroupRepository->findOrFail($taskGroupId);

        $this->taskGroupAccessPolicy->assertOwner($taskGroup->ownerId, $userId);

        if ($this->taskGroupRepository->containsTask($taskGroupId, $taskId)) {
            return TaskGroupViewModelMapper::fromModel($taskGroup);
        }

        $this->taskGroupRepository->attachTask($taskGroupId, $taskId);
        $this->messageBus->dispatch(new TaskGroupsRecalculationByGroupIdsRequestedEvent(
            taskGroupIds: [$taskGroupId],
        ));

        return TaskGroupViewModelMapper::fromModel($taskGroup);
    }
}
