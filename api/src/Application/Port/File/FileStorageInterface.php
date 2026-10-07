<?php

declare(strict_types=1);

namespace App\Application\Port\File;

interface FileStorageInterface
{
    /** @param resource|string $contents */
    public function write(string $storageKey, mixed $contents): void;

    public function move(string $fromKey, string $toKey): void;

    public function delete(string $storageKey): void;

    public function exists(string $storageKey): bool;

    /** @return resource */
    public function readStream(string $storageKey);
}
