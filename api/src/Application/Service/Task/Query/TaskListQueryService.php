<?php

declare(strict_types=1);

namespace App\Application\Service\Task\Query;

use App\Application\Model\Common\PaginationModel;
use App\Application\Model\Task\Read\TaskPageModel;
use App\Application\Port\Task\TaskRepositoryInterface;

final readonly class TaskListQueryService
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function execute(string $userId, PaginationModel $pagination): TaskPageModel
    {
        return $this->taskRepository->listViewPageByUserId($userId, $pagination);
    }
}
