<?php

declare(strict_types=1);

namespace App\Application\Service\Task\Lifecycle;

use App\Application\Enum\Task\TaskUserStatusEnum;
use App\Application\Model\Task\TaskUserModel;
use InvalidArgumentException;

final class TaskUserListService
{
    /**
     * @param list<TaskUserModel> $users
     *
     * @return list<string>
     */
    public static function ids(array $users): array
    {
        return array_map(
            static fn (TaskUserModel $user): string => $user->userId,
            $users,
        );
    }

    /**
     * @param list<TaskUserModel> $users
     */
    public static function has(array $users, string $userId): bool
    {
        foreach ($users as $user) {
            if ($user->userId === $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Empty assignment list means the task is open to any user.
     *
     * @param list<TaskUserModel> $users
     */
    public static function belongsTo(array $users, string $userId): bool
    {
        return $users === [] || self::has($users, $userId);
    }

    /**
     * @param list<TaskUserModel> $users
     *
     * @return list<TaskUserModel>
     */
    public static function assign(
        array $users,
        string $userId,
        TaskUserStatusEnum $status = TaskUserStatusEnum::Initial,
    ): array {
        $result = [];
        $found = false;

        foreach ($users as $user) {
            if ($user->userId === $userId) {
                $result[] = $user->withForcedStatus($status);
                $found = true;
                continue;
            }

            $result[] = $user;
        }

        if (!$found) {
            $result[] = TaskUserModel::create($userId, $status);
        }

        return $result;
    }

    /**
     * @param list<TaskUserModel> $users
     *
     * @return list<TaskUserModel>
     */
    public static function unassign(array $users, string $userId): array
    {
        return array_values(array_filter(
            $users,
            static fn (TaskUserModel $user): bool => $user->userId !== $userId,
        ));
    }

    /**
     * @param list<TaskUserModel> $users
     *
     * @return list<TaskUserModel>
     */
    public static function changeStatus(array $users, string $userId, TaskUserStatusEnum $status): array
    {
        $result = [];
        $found = false;

        foreach ($users as $user) {
            if ($user->userId === $userId) {
                $result[] = $user->withStatus($status);
                $found = true;
                continue;
            }

            $result[] = $user;
        }

        if (!$found) {
            throw new InvalidArgumentException(sprintf('User "%s" is not assigned to this task.', $userId));
        }

        return $result;
    }
}
