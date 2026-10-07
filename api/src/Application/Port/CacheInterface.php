<?php

declare(strict_types=1);

namespace App\Application\Port;

interface CacheInterface
{
    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public function get(string $key, callable $callback, int $ttlSeconds = 60): mixed;

    public function delete(string $key): void;
}
