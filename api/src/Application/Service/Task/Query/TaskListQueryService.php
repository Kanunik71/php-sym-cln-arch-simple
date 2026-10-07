<?php

declare(strict_types=1);

namespace App\Application\Service\Task\Query;

use App\Application\Cache\Task\TaskCache;
use App\Application\Model\Task\Read\TaskViewModel;
use App\Application\Port\Task\TaskRepositoryInterface;

final readonly class TaskListQueryService
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private TaskCache $taskCache,
    ) {
    }

    /** @return list<TaskViewModel> */
    public function execute(string $userId): array
    {
        return $this->taskCache->getUserList(
            $userId,
            fn () => $this->taskRepository->listViewsByUserId($userId),
        );
    }
}
