<?php

declare(strict_types=1);

namespace App\Application\Service\File\Action;

use App\Application\Mapper\File\FileViewModelMapper;
use App\Application\Model\File\FileModel;
use App\Application\Model\File\Read\FileViewModel;
use App\Application\Policy\File\UploadFilePolicy;
use App\Application\Port\File\FileRepositoryInterface;
use App\Application\Port\File\FileStorageInterface;
use Throwable;

final readonly class FileUploadTmpService
{
    public function __construct(
        private UploadFilePolicy $uploadFilePolicy,
        private FileRepositoryInterface $fileRepository,
        private FileStorageInterface $fileStorage,
    ) {
    }

    /** @param resource|string $contents */
    public function execute(
        string $userId,
        string $originalName,
        string $mimeType,
        int $sizeBytes,
        mixed $contents,
    ): FileViewModel {
        $this->uploadFilePolicy->assertValidUpload($mimeType, $sizeBytes);

        $file = FileModel::createTemporary(
            ownerId: $userId,
            originalName: $originalName,
            mimeType: $mimeType,
            sizeBytes: $sizeBytes,
        );

        $file = $this->fileRepository->save($file);

        try {
            $this->fileStorage->write($file->storageKey, $contents);
        } catch (Throwable $exception) {
            $this->fileRepository->delete($file);

            throw $exception;
        }

        return FileViewModelMapper::fromModel($file);
    }
}
