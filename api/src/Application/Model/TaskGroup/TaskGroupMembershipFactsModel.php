<?php

declare(strict_types=1);

namespace App\Application\Model\TaskGroup;

final readonly class TaskGroupMembershipFactsModel
{
    public function __construct(
        public int $taskCount,
        public bool $hasNonTerminalTask,
    ) {
    }
}
