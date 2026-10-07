<?php

declare(strict_types=1);

namespace App\Application\Mapper\Common;

use App\Application\Model\Common\FileItemModel;
use App\Application\Port\File\FileDownloadUrlProviderInterface;

final class FileItemModelMapper
{
    public static function fromFileId(?string $fileId, FileDownloadUrlProviderInterface $urlProvider): ?FileItemModel
    {
        if ($fileId === null) {
            return null;
        }

        return new FileItemModel(
            id: $fileId,
            url: $urlProvider->buildDownloadUrl($fileId),
        );
    }

    /**
     * @param list<string> $fileIds
     *
     * @return list<FileItemModel>
     */
    public static function fromFileIds(array $fileIds, FileDownloadUrlProviderInterface $urlProvider): array
    {
        return array_map(
            static fn (string $fileId): FileItemModel => new FileItemModel(
                id: $fileId,
                url: $urlProvider->buildDownloadUrl($fileId),
            ),
            $fileIds,
        );
    }

    /**
     * @param list<string> $fileIds
     */
    public static function firstFromFileIds(array $fileIds, FileDownloadUrlProviderInterface $urlProvider): ?FileItemModel
    {
        return self::fromFileId($fileIds[0] ?? null, $urlProvider);
    }
}
