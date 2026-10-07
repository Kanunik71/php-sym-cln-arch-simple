<?php

declare(strict_types=1);

namespace App\Application\Service\Task\Query;

use App\Application\Model\Task\Read\TaskViewModel;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Port\Task\TaskRepositoryInterface;

final readonly class TaskQueryService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function execute(string $taskId, string $userId): TaskViewModel
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->taskAccessPolicy->assertUserCanAccessIo($task, $userId);

        return $this->taskRepository->findViewOrFail($taskId);
    }
}
