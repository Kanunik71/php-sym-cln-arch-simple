<?php

declare(strict_types=1);

namespace App\Application\Model\File;

use App\Application\Enum\File\FileStatusEnum;
use App\Shared\Utils\Asserts\StringAssertUtils;
use App\Shared\Utils\UidUtils;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Persistence model — mirrors files table columns (scalars only).
 */
final readonly class FileModel
{
    /** Persisted column max length (files.original_name). */
    public const ORIGINAL_NAME_MAX_LENGTH = 255;

    /** Persisted column max length (files.mime_type). */
    public const MIME_TYPE_MAX_LENGTH = 127;

    /** Persisted column max length (files.storage_key). */
    public const STORAGE_KEY_MAX_LENGTH = 512;

    public string $originalName;
    public string $mimeType;
    public int $sizeBytes;
    public string $storageKey;

    public function __construct(
        public string $id,
        public string $ownerId,
        string $originalName,
        string $mimeType,
        int $sizeBytes,
        string $storageKey,
        public FileStatusEnum $status,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
        $this->originalName = StringAssertUtils::notBlankMaxLength(
            $originalName,
            self::ORIGINAL_NAME_MAX_LENGTH,
            'File original name',
        );
        $this->mimeType = StringAssertUtils::notBlankMaxLength(
            $mimeType,
            self::MIME_TYPE_MAX_LENGTH,
            'File mime type',
        );
        $this->sizeBytes = self::assertPositiveSize($sizeBytes);
        $this->storageKey = StringAssertUtils::notBlankMaxLength(
            $storageKey,
            self::STORAGE_KEY_MAX_LENGTH,
            'File storage key',
        );
    }

    public static function createTemporary(
        string $ownerId,
        string $originalName,
        string $mimeType,
        int $sizeBytes,
    ): self {
        $id = UidUtils::generateString();
        $now = new DateTimeImmutable();

        return new self(
            id: $id,
            ownerId: $ownerId,
            originalName: $originalName,
            mimeType: $mimeType,
            sizeBytes: $sizeBytes,
            storageKey: self::temporaryStorageKey($id),
            status: FileStatusEnum::Temporary,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function temporaryStorageKey(string $id): string
    {
        return 'tmp/'.$id;
    }

    public static function permanentStorageKey(string $id): string
    {
        return 'permanent/'.$id;
    }

    public function isTemporary(): bool
    {
        return $this->status === FileStatusEnum::Temporary;
    }

    public function promotedToPermanent(): self
    {
        if ($this->status === FileStatusEnum::Permanent) {
            return $this;
        }

        return new self(
            id: $this->id,
            ownerId: $this->ownerId,
            originalName: $this->originalName,
            mimeType: $this->mimeType,
            sizeBytes: $this->sizeBytes,
            storageKey: self::permanentStorageKey($this->id),
            status: FileStatusEnum::Permanent,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }

    private static function assertPositiveSize(int $sizeBytes): int
    {
        if ($sizeBytes <= 0) {
            throw new InvalidArgumentException('File size must be positive.');
        }

        return $sizeBytes;
    }
}
