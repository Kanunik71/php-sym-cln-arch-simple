<?php

declare(strict_types=1);

namespace App\Application\Port\TaskGroup;

use App\Application\Model\TaskGroup\TaskGroupMembershipFactsModel;
use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Application\Model\TaskGroup\Read\TaskStatusCountsModel;

interface TaskGroupRepositoryInterface
{
    public function save(TaskGroupModel $taskGroup): TaskGroupModel;

    public function findById(string $id): ?TaskGroupModel;

    public function findOrFail(string $id): TaskGroupModel;

    /** @return list<TaskGroupModel> */
    public function listAll(): array;

    /**
     * Stable page for full-table batch scans (not API pagination).
     *
     * @return list<TaskGroupModel>
     */
    public function listBatch(int $offset, int $limit): array;

    /** @return list<TaskGroupModel> */
    public function listByTaskId(string $taskId): array;

    /**
     * @param list<string> $taskGroupIds
     *
     * @return array<string, TaskStatusCountsModel>
     */
    public function listTaskStatusCountsByIds(array $taskGroupIds): array;

    /**
     * @param list<string> $ids
     *
     * @return list<TaskGroupModel>
     */
    public function listByIds(array $ids): array;

    public function containsTask(string $taskGroupId, string $taskId): bool;

    public function attachTask(string $taskGroupId, string $taskId): void;

    public function detachTask(string $taskGroupId, string $taskId): void;

    /**
     * @param list<string> $taskGroupIds
     *
     * @return array<string, TaskGroupMembershipFactsModel>
     */
    public function listMembershipFactsByIds(array $taskGroupIds): array;

    public function delete(TaskGroupModel $taskGroup): void;
}
