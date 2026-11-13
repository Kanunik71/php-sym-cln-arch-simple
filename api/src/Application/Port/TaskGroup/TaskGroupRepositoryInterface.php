<?php

declare(strict_types=1);

namespace App\Application\Port\TaskGroup;

use App\Application\Model\TaskGroup\Read\TaskStatusCountsModel;
use App\Application\Model\TaskGroup\TaskGroupMembershipFactsModel;
use App\Application\Model\TaskGroup\TaskGroupModel;

interface TaskGroupRepositoryInterface
{
    public function save(TaskGroupModel $taskGroup): TaskGroupModel;

    public function findById(string $id): ?TaskGroupModel;

    public function findOrFail(string $id): TaskGroupModel;

    /**
     * Batch of rows before the given cursor, ordered by id descending — for full-table scans.
     *
     * The cursor is the id of the first row of the previous batch; the first
     * call passes the highest possible id. Keyset pagination keeps a full
     * traversal linear: each batch continues where the previous one stopped,
     * without re-reading and discarding the skipped rows the way OFFSET does.
     *
     * @return list<TaskGroupModel>
     */
    public function listBatchBefore(string $beforeId, int $limit): array;

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

    public function deleteByOwnerId(string $ownerId): void;
}
