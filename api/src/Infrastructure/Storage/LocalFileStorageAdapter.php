<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Port\File\FileStorageInterface;
use RuntimeException;

final readonly class LocalFileStorageAdapter implements FileStorageInterface
{
    public function __construct(
        private string $basePath,
    ) {
    }

    public function write(string $storageKey, mixed $contents): void
    {
        $path = $this->resolvePath($storageKey);
        $this->ensureParentDirectory($path);

        if (is_resource($contents)) {
            $target = fopen($path, 'wb');
            if ($target === false) {
                throw new RuntimeException(sprintf('Cannot open file "%s" for writing.', $path));
            }

            stream_copy_to_stream($contents, $target);
            fclose($target);

            return;
        }

        if (!is_string($contents)) {
            throw new RuntimeException('File contents must be a string or stream resource.');
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException(sprintf('Cannot write file "%s".', $path));
        }
    }

    public function move(string $fromKey, string $toKey): void
    {
        $fromPath = $this->resolvePath($fromKey);
        $toPath = $this->resolvePath($toKey);

        if (!is_file($fromPath)) {
            return;
        }

        $this->ensureParentDirectory($toPath);

        if (!rename($fromPath, $toPath)) {
            throw new RuntimeException(sprintf('Cannot move file from "%s" to "%s".', $fromKey, $toKey));
        }
    }

    public function delete(string $storageKey): void
    {
        $path = $this->resolvePath($storageKey);

        if (!is_file($path)) {
            return;
        }

        if (!unlink($path)) {
            throw new RuntimeException(sprintf('Cannot delete file "%s".', $path));
        }
    }

    public function exists(string $storageKey): bool
    {
        return is_file($this->resolvePath($storageKey));
    }

    public function readStream(string $storageKey)
    {
        $path = $this->resolvePath($storageKey);

        if (!is_file($path)) {
            throw new RuntimeException(sprintf('File "%s" does not exist in storage.', $storageKey));
        }

        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException(sprintf('Cannot read file "%s".', $path));
        }

        return $stream;
    }

    private function resolvePath(string $storageKey): string
    {
        $normalized = str_replace('\\', '/', $storageKey);
        if ($normalized === '' || str_contains($normalized, '..')) {
            throw new RuntimeException('Invalid storage key.');
        }

        return $this->basePath.'/'.$normalized;
    }

    private function ensureParentDirectory(string $path): void
    {
        $directory = dirname($path);

        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Cannot create directory "%s".', $directory));
        }
    }
}
