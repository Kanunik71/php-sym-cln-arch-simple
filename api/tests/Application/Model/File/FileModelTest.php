<?php

declare(strict_types=1);

namespace App\Tests\Application\Model\File;

use App\Application\Enum\File\FileStatusEnum;
use App\Application\Model\File\FileModel;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FileModelTest extends TestCase
{
    public function testCreateTemporaryUsesTmpStorageKey(): void
    {
        $file = FileModel::createTemporary(
            ownerId: 'user-1',
            originalName: 'avatar.jpg',
            mimeType: 'image/jpeg',
            sizeBytes: 1024,
        );

        $this->assertSame(FileStatusEnum::Temporary, $file->status);
        $this->assertSame('tmp/'.$file->id, $file->storageKey);
    }

    public function testPromotedToPermanentMovesStorageKey(): void
    {
        $file = FileModel::createTemporary(
            ownerId: 'user-1',
            originalName: 'avatar.jpg',
            mimeType: 'image/jpeg',
            sizeBytes: 1024,
        );

        $promoted = $file->promotedToPermanent();

        $this->assertSame(FileStatusEnum::Permanent, $promoted->status);
        $this->assertSame('permanent/'.$file->id, $promoted->storageKey);
        $this->assertSame(FileStatusEnum::Temporary, $file->status);
    }

    public function testCreateTemporaryRejectsNonPositiveSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File size must be positive.');

        FileModel::createTemporary(
            ownerId: 'user-1',
            originalName: 'avatar.jpg',
            mimeType: 'image/jpeg',
            sizeBytes: 0,
        );
    }

    public function testCreateTemporaryRejectsOriginalNameExceedingMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'File original name must not exceed %d characters.',
            FileModel::ORIGINAL_NAME_MAX_LENGTH,
        ));

        FileModel::createTemporary(
            ownerId: 'user-1',
            originalName: str_repeat('a', FileModel::ORIGINAL_NAME_MAX_LENGTH + 1).'.jpg',
            mimeType: 'image/jpeg',
            sizeBytes: 10,
        );
    }
}
