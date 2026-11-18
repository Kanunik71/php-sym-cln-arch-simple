<?php

declare(strict_types=1);

namespace App\Application\Service\Task\Action;

use App\Application\Event\TaskGroup\TaskGroupsRecalculationByGroupIdsRequestedEvent;
use App\Application\Policy\Task\TaskAccessPolicy;
use App\Application\Port\Asset\AssetRepositoryInterface;
use App\Application\Port\Messaging\MessageBusInterface;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Application\Port\TransactionManagerInterface;
use App\Shared\Utils\ArrayUtils;

final readonly class TaskDeleteService
{
    public function __construct(
        private TaskAccessPolicy $taskAccessPolicy,
        private TaskRepositoryInterface $taskRepository,
        private TaskGroupRepositoryInterface $taskGroupRepository,
        private AssetRepositoryInterface $assetRepository,
        private MessageBusInterface $messageBus,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function execute(string $taskId, string $userId): void
    {
        $task = $this->taskRepository->findOrFail($taskId);

        $this->taskAccessPolicy->assertOwner($task->ownerId, $userId);

        $affectedGroupIds = ArrayUtils::ids(
            $this->taskGroupRepository->listByTaskId($taskId),
        );

        $affectedAssetIds = $this->assetRepository->listIdsByTaskId($taskId);

        $this->transactionManager->run(
            function () use ($task, $taskId, $affectedGroupIds, $affectedAssetIds): void {
                $this->detachFromGroups($taskId, $affectedGroupIds);
                $this->detachFromAssets($taskId, $affectedAssetIds);

                $this->taskRepository->delete($task);
            },
        );

        if ($affectedGroupIds === []) {
            return;
        }

        $this->messageBus->dispatch(new TaskGroupsRecalculationByGroupIdsRequestedEvent(
            taskGroupIds: $affectedGroupIds,
        ));
    }

    /**
     * @param list<string> $taskGroupIds
     */
    private function detachFromGroups(string $taskId, array $taskGroupIds): void
    {
        foreach ($taskGroupIds as $taskGroupId) {
            $this->taskGroupRepository->detachTask($taskGroupId, $taskId);
        }
    }

    /**
     * @param list<string> $assetIds
     */
    private function detachFromAssets(string $taskId, array $assetIds): void
    {
        foreach ($assetIds as $assetId) {
            $this->assetRepository->detachTask($assetId, $taskId);
        }
    }
}
