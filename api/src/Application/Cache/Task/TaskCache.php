<?php

declare(strict_types=1);

namespace App\Application\Cache\Task;

use App\Application\Port\CacheInterface;

final readonly class TaskCache
{
    private const USER_LIST_TTL = 60;

    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public function getUserList(string $userId, callable $callback): mixed
    {
        return $this->cache->get($this->userListKey($userId), $callback, self::USER_LIST_TTL);
    }

    public function invalidateUserList(string $userId): void
    {
        $this->cache->delete($this->userListKey($userId));
    }

    public function invalidateUserLists(string ...$userIds): void
    {
        foreach (array_unique($userIds) as $userId) {
            $this->invalidateUserList($userId);
        }
    }

    private function userListKey(string $userId): string
    {
        return sprintf('tasks.user.%s', $userId);
    }
}
