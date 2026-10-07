<?php

declare(strict_types=1);

namespace App\Application\Service\TaskGroup\Lifecycle;

use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Application\Service\TaskGroup\Lifecycle\TaskGroupRecalculationService;

final readonly class TaskGroupRecalculateService
{
    public function __construct(
        private TaskGroupRepositoryInterface $taskGroupRepository,
        private TaskGroupRecalculationService $recalculationService,
    ) {
    }

    public function execute(string $taskGroupId): void
    {
        $this->taskGroupRepository->findOrFail($taskGroupId);
        $this->recalculationService->recalculateByGroupIds([$taskGroupId]);
    }
}
