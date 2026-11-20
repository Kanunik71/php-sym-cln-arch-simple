<?php

declare(strict_types=1);

namespace App\Application\Event\TaskGroup;

use App\Shared\Utils\ArrayUtils;

final readonly class TaskGroupRecalculationByGroupIdsRequestedEvent
{
    /**
     * @var list<string>
     */
    public array $taskGroupIds;

    /**
     * @param list<string> $taskGroupIds
     */
    public function __construct(array $taskGroupIds)
    {
        $this->taskGroupIds = ArrayUtils::uniqueSorted($taskGroupIds);
    }
}
