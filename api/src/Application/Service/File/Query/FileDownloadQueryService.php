<?php

declare(strict_types=1);

namespace App\Application\Service\File\Query;

use App\Application\Model\File\Read\DownloadFileModel;
use App\Application\Policy\File\FileAccessPolicy;
use App\Application\Port\File\FileRepositoryInterface;
use App\Application\Port\File\FileStorageInterface;

final readonly class FileDownloadQueryService
{
    public function __construct(
        private FileAccessPolicy $fileAccessPolicy,
        private FileRepositoryInterface $fileRepository,
        private FileStorageInterface $fileStorage,
    ) {
    }

    public function execute(string $userId, string $fileId): DownloadFileModel
    {
        $file = $this->fileRepository->findOrFail($fileId);
        $this->fileAccessPolicy->assertUserCanAccess($file, $userId);

        return new DownloadFileModel(
            originalName: $file->originalName,
            mimeType: $file->mimeType,
            stream: $this->fileStorage->readStream($file->storageKey),
        );
    }
}
