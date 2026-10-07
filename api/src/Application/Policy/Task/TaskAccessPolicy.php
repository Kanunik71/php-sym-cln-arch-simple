<?php

declare(strict_types=1);

namespace App\Application\Policy\Task;

use App\Application\Exception\Task\UnauthorizedTaskAccessException;
use App\Application\Model\Task\TaskModel;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Service\Task\Lifecycle\TaskUserListService;

final readonly class TaskAccessPolicy
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function assertUserCanAccessIo(TaskModel $task, string $userId): void
    {
        $users = $this->taskRepository->listUsers($task->id);

        if (!TaskUserListService::belongsTo($users, $userId)) {
            throw UnauthorizedTaskAccessException::create();
        }
    }
}
