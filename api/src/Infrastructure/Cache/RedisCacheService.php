<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Application\Port\CacheInterface;
use Symfony\Contracts\Cache\CacheInterface as SymfonyCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class RedisCacheService implements CacheInterface
{
    public function __construct(
        private SymfonyCacheInterface $cache,
    ) {
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public function get(string $key, callable $callback, int $ttlSeconds = 60): mixed
    {
        return $this->cache->get($key, function (ItemInterface $item) use ($callback, $ttlSeconds): mixed {
            $item->expiresAfter($ttlSeconds);

            return $callback();
        });
    }

    public function delete(string $key): void
    {
        $this->cache->delete($key);
    }
}
