<?php

declare(strict_types=1);

namespace App\Application\Service\Task\Action;

use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Shared\Utils\ArrayUtils;

final readonly class TaskDeleteService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private TaskGroupRepositoryInterface $taskGroupRepository,
        private TaskGroupRecalculationDispatcher $recalculationDispatcher,
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

        $this->recalculationDispatcher->requestByGroupIds($affectedGroupIds);
    }
}
