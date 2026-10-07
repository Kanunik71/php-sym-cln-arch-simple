<?php

declare(strict_types=1);

namespace App\Application\Port\Task;

use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Model\Task\TaskModel;
use App\Application\Model\Task\TaskUserModel;
use App\Application\Model\Task\Read\TaskViewModel;

interface TaskRepositoryInterface
{
    /**
     * @param list<TaskUserModel>|null $users null = leave existing task_users unchanged
     */
    public function save(TaskModel $task, ?array $users = null): TaskModel;

    public function findById(string $id): ?TaskModel;

    public function findOrFail(string $id): TaskModel;

    public function findViewOrFail(string $id): TaskViewModel;

    /**
     * @param list<string> $ids
     *
     * @return list<TaskModel>
     */
    public function listByIds(array $ids): array;

    /** @return list<TaskViewModel> */
    public function listViewsByUserId(string $userId): array;

    /** @return list<TaskModel> */
    public function listByUserId(string $userId): array;

    /** @return list<TaskUserModel> */
    public function listUsers(string $taskId): array;

    /**
     * @param list<string> $ids
     *
     * @return array<string, TaskStatusEnum>
     */
    public function listStatusesByIds(array $ids): array;

    public function delete(TaskModel $task): void;
}
