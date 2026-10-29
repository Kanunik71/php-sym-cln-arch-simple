<?php

declare(strict_types=1);

namespace App\Application\Service\File\Query;

use App\Application\Exception\File\UnauthorizedFileAccessException;
use App\Application\Model\File\Read\DownloadFileModel;
use App\Application\Port\File\FileDownloadUrlProviderInterface;
use App\Application\Port\File\FileRepositoryInterface;
use App\Application\Port\File\FileStorageInterface;

final readonly class FileDownloadQueryService
{
    public function __construct(
        private FileDownloadUrlProviderInterface $downloadUrls,
        private FileRepositoryInterface $fileRepository,
        private FileStorageInterface $fileStorage,
    ) {
    }

    public function execute(string $fileId, ?string $expiresAt, ?string $signature): DownloadFileModel
    {
        if (!$this->downloadUrls->matches($fileId, $expiresAt, $signature)) {
            throw UnauthorizedFileAccessException::invalidSignature($fileId);
        }

        $file = $this->fileRepository->findOrFail($fileId);

        return new DownloadFileModel(
            originalName: $file->originalName,
            mimeType: $file->mimeType,
            stream: $this->fileStorage->readStream($file->storageKey),
        );
    }
}
