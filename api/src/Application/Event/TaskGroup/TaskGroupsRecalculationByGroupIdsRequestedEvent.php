<?php

declare(strict_types=1);

namespace App\Application\Event\TaskGroup;

use App\Shared\Utils\ArrayUtils;

/**
 * Пересчёт указанных групп.
 *
 * Payload канонический: без дублей, в детерминированном порядке.
 */
final readonly class TaskGroupsRecalculationByGroupIdsRequestedEvent
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
