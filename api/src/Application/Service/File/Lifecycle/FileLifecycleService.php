<?php

declare(strict_types=1);

namespace App\Application\Service\File\Lifecycle;

use App\Application\Model\File\FileModel;
use App\Application\Policy\File\AttachFilePolicy;
use App\Application\Port\File\FileRepositoryInterface;
use App\Application\Port\File\FileStorageInterface;

final readonly class FileLifecycleService
{
    public function __construct(
        private AttachFilePolicy $attachFilePolicy,
        private FileRepositoryInterface $fileRepository,
        private FileStorageInterface $fileStorage,
    ) {
    }

    /** @param list<string> $fileIds */
    public function attachFiles(array $fileIds, string $ownerUserId): void
    {
        foreach ($fileIds as $fileId) {
            $this->promoteById($fileId, $ownerUserId);
        }
    }

    /**
     * @param list<string> $oldFileIds
     * @param list<string> $newFileIds
     */
    public function syncFileIds(array $oldFileIds, array $newFileIds, string $ownerUserId): void
    {
        $toRemove = array_values(array_diff($oldFileIds, $newFileIds));
        $toAdd = array_values(array_diff($newFileIds, $oldFileIds));

        foreach ($toAdd as $fileId) {
            $this->promoteById($fileId, $ownerUserId);
        }

        foreach ($toRemove as $fileId) {
            $this->deleteById($fileId);
        }
    }

    /** @param list<string> $fileIds */
    public function detachFiles(array $fileIds): void
    {
        $this->purgeStorage($this->deleteRecords($fileIds));
    }

    public function promoteById(string $fileId, string $ownerUserId): FileModel
    {
        $file = $this->fileRepository->findOrFail($fileId);
        $this->attachFilePolicy->assertCanAttach($file, $ownerUserId);

        return $this->promote($file);
    }

    public function deleteById(string $fileId): void
    {
        $file = $this->fileRepository->findById($fileId);
        if ($file !== null) {
            $this->delete($file);
        }
    }

    public function delete(FileModel $file): void
    {
        $this->purgeStorage($this->deleteRecords([$file->id]));
    }

    /**
     * Deletes file DB rows. Returns storage keys to purge after the surrounding DB transaction commits.
     *
     * @param list<string> $fileIds
     *
     * @return list<string>
     */
    public function deleteRecords(array $fileIds): array
    {
        $storageKeys = [];

        foreach ($fileIds as $fileId) {
            $file = $this->fileRepository->findById($fileId);
            if ($file === null) {
                continue;
            }

            $storageKeys[] = $file->storageKey;
            $this->fileRepository->delete($file);
        }

        return $storageKeys;
    }

    /** @param list<string> $storageKeys */
    public function purgeStorage(array $storageKeys): void
    {
        foreach ($storageKeys as $storageKey) {
            $this->fileStorage->delete($storageKey);
        }
    }

    private function promote(FileModel $file): FileModel
    {
        if (!$file->isTemporary()) {
            return $file;
        }

        $fromKey = $file->storageKey;
        $promoted = $file->promotedToPermanent();
        $this->fileStorage->move($fromKey, $promoted->storageKey);

        return $this->fileRepository->save($promoted);
    }
}
