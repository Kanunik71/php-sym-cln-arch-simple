<?php

declare(strict_types=1);

namespace App\Tests\Application\Cache\TaskGroup;

use App\Application\Cache\TaskGroup\TaskGroupSummaryCache;
use App\Application\Model\TaskGroup\Read\TaskGroupSummaryModel;
use App\Application\Model\TaskGroup\Read\TaskStatusCountsModel;
use App\Application\Port\CacheInterface;
use PHPUnit\Framework\TestCase;

/**
 * The cache owns the key and the TTL, the loader is supplied by the caller.
 */
final class TaskGroupSummaryCacheTest extends TestCase
{
    public function testLoadsThroughCacheOnMiss(): void
    {
        $port = new InMemoryCachePort();
        $cache = new TaskGroupSummaryCache($port, 30);

        $calls = 0;
        $loader = function () use (&$calls): array {
            ++$calls;

            return [self::summary('group-1')];
        };

        $summaries = $cache->get($loader);

        $this->assertCount(1, $summaries);
        $this->assertSame('group-1', $summaries[0]->id);
        $this->assertSame(1, $calls);
        $this->assertSame(TaskGroupSummaryCache::KEY, $port->lastKey);
        $this->assertSame(30, $port->lastTtl);
    }

    public function testServesCachedValueWithoutCallingLoader(): void
    {
        $port = new InMemoryCachePort();
        $cache = new TaskGroupSummaryCache($port);

        $calls = 0;
        $loader = function () use (&$calls): array {
            ++$calls;

            return [self::summary('group-1')];
        };

        $cache->get($loader);
        $second = $cache->get($loader);

        $this->assertSame(1, $calls);
        $this->assertCount(1, $second);
        $this->assertSame('group-1', $second[0]->id);
    }

    public function testInvalidateDropsCachedValue(): void
    {
        $port = new InMemoryCachePort();
        $cache = new TaskGroupSummaryCache($port);

        $calls = 0;
        $loader = function () use (&$calls): array {
            ++$calls;

            return [self::summary('group-1')];
        };

        $cache->get($loader);
        $cache->invalidate();
        $cache->get($loader);

        $this->assertSame(2, $calls);
        $this->assertSame([TaskGroupSummaryCache::KEY], $port->deletedKeys);
    }

    private static function summary(string $id): TaskGroupSummaryModel
    {
        return new TaskGroupSummaryModel(
            id: $id,
            name: 'Release',
            taskStatusCounts: TaskStatusCountsModel::empty(),
        );
    }
}

/**
 * Test double for the cache port: stores one value per key.
 */
final class InMemoryCachePort implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $values = [];

    /** @var list<string> */
    public array $deletedKeys = [];

    public ?string $lastKey = null;

    public ?int $lastTtl = null;

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public function get(string $key, callable $callback, int $ttlSeconds = 60): mixed
    {
        $this->lastKey = $key;
        $this->lastTtl = $ttlSeconds;

        if (!array_key_exists($key, $this->values)) {
            $this->values[$key] = $callback();
        }

        /** @var T $value */
        $value = $this->values[$key];

        return $value;
    }

    public function delete(string $key): void
    {
        $this->deletedKeys[] = $key;
        unset($this->values[$key]);
    }
}
