<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait\Cache;

use App\Application\Cache\Task\TaskCache;
use PHPUnit\Framework\Assert;

trait AssertTaskCacheTrait
{
    protected function warmTaskUserListCache(string $userId, mixed $stale = ['stale']): void
    {
        $this->taskCache()->getUserList($userId, fn (): mixed => $stale);
    }

    /**
     * @template T
     *
     * @param (callable(): T)|null $callback
     *
     * @return ($callback is null ? array{} : T)
     */
    protected function assertTaskUserListCacheInvalidated(string $userId, ?callable $callback = null): mixed
    {
        $cacheMiss = false;

        $result = $this->taskCache()->getUserList($userId, function () use (&$cacheMiss, $callback): mixed {
            $cacheMiss = true;

            return $callback !== null ? $callback() : [];
        });

        Assert::assertTrue($cacheMiss, 'Expected task user list cache to be invalidated.');

        return $result;
    }

    private function taskCache(): TaskCache
    {
        return $this->bed->get(TaskCache::class);
    }
}
