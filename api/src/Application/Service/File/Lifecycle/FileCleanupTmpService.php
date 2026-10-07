<?php

declare(strict_types=1);

namespace App\Application\Service\File\Lifecycle;

use App\Application\Port\File\FileRepositoryInterface;
use App\Application\Port\File\FileStorageInterface;
use DateInterval;
use DateTimeImmutable;

final readonly class FileCleanupTmpService
{
    public function __construct(
        private FileRepositoryInterface $fileRepository,
        private FileStorageInterface $fileStorage,
        private int $tmpTtlHours,
    ) {
    }

    public function execute(): int
    {
        $updatedBefore = (new DateTimeImmutable())->sub(new DateInterval(sprintf('PT%dH', $this->tmpTtlHours)));
        $expiredFiles = $this->fileRepository->listExpiredTemporary($updatedBefore);
        $deletedCount = 0;

        foreach ($expiredFiles as $file) {
            $this->fileStorage->delete($file->storageKey);
            $this->fileRepository->delete($file);
            ++$deletedCount;
        }

        return $deletedCount;
    }
}
