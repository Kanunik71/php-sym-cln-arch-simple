<?php

declare(strict_types=1);

namespace App\Application\Service\Task\Action;

use App\Application\Cache\Task\TaskCache;
use App\Application\Dispatcher\TaskGroup\TaskGroupRecalculationDispatcher;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Application\Service\Task\Lifecycle\TaskUserListService;
use App\Shared\Utils\ArrayUtils;

final readonly class TaskDeleteService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private TaskGroupRepositoryInterface $taskGroupRepository,
        private TaskCache $taskCache,
        private TaskGroupRecalculationDispatcher $recalculationDispatcher,
    ) {
    }

    public function execute(string $taskId, string $userId): void
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);

        $userIdsToInvalidate = [
            $userId,
            ...TaskUserListService::ids($this->taskRepository->listUsers($task->id)),
        ];

        $affectedGroupIds = ArrayUtils::ids(
            $this->taskGroupRepository->listByTaskId($taskId),
        );

        $this->taskRepository->delete($task);

        $this->taskCache->invalidateUserLists(...$userIdsToInvalidate);

        $this->recalculationDispatcher->requestByGroupIds($affectedGroupIds);
    }
}
