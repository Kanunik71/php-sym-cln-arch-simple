<?php

declare(strict_types=1);

namespace App\Application\Dispatcher\TaskGroup;

use App\Application\Event\TaskGroup\TaskGroupsRecalculationByGroupIdsRequestedEvent;
use App\Application\Event\TaskGroup\TaskGroupsRecalculationByTaskRequestedEvent;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Ставит пересчёт TaskGroup в очередь.
 *
 * Продюсер не держит лок: HTTP-запрос короткий и не может ждать конца
 * асинхронной работы. Одновременность ограничивает потребитель — он берёт
 * лок на канал пересчёта и не запускает второй пересчёт, пока первый не
 * закончен.
 */
final readonly class TaskGroupRecalculationDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function requestByTaskId(string $taskId): void
    {
        $this->messageBus->dispatch(new TaskGroupsRecalculationByTaskRequestedEvent(taskId: $taskId));
    }

    /**
     * @param list<string> $taskGroupIds
     */
    public function requestByGroupIds(array $taskGroupIds): void
    {
        if ($taskGroupIds === []) {
            return;
        }

        $uniqueGroupIds = array_values(array_unique($taskGroupIds));
        sort($uniqueGroupIds);

        $this->messageBus->dispatch(new TaskGroupsRecalculationByGroupIdsRequestedEvent(taskGroupIds: $uniqueGroupIds));
    }
}
