<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait\TestBed;

use App\Application\Service\File\Action\FileUploadTmpService;
use App\Application\Model\File\FileModel;
use App\Application\Port\File\FileRepositoryInterface;

trait FileTestBedTrait
{
    public function fileRepository(): FileRepositoryInterface
    {
        return $this->files;
    }

    public function uploadTmpImage(string $ownerId, string $originalName = 'avatar.jpg'): FileModel
    {
        $uploadTmpFile = $this->get(FileUploadTmpService::class);
        $dto = $uploadTmpFile->execute(
            userId: $ownerId,
            originalName: $originalName,
            mimeType: 'image/jpeg',
            sizeBytes: strlen(self::minimalJpegBytes()),
            contents: self::minimalJpegBytes(),
        );

        return $this->files->findOrFail($dto->id);
    }

    public function findFileById(string $id): ?FileModel
    {
        return $this->files->findById($id);
    }

    private static function minimalJpegBytes(): string
    {
        return base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDc+LjEyOUHT0tPVl9jX2Nvg4eKz4+UAAAD/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjP/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAB//2Q==',
            true,
        ) ?: '';
    }
}
