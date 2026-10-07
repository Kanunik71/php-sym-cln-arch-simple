<?php

declare(strict_types=1);

namespace App\Application\Policy\Task;

use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Exception\Task\TaskNotFoundException;

final readonly class CreateTaskPolicy
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
    ) {
    }

    public function assertParentExistsIo(?string $parentId): void
    {
        if ($parentId !== null && $this->taskRepository->findById($parentId) === null) {
            throw TaskNotFoundException::withId($parentId);
        }
    }
}
