<?php

declare(strict_types=1);

namespace App\Application\Mapper\File;

use App\Application\Model\File\FileModel;
use App\Application\Model\File\Read\FileViewModel;
use App\Shared\Utils\DateUtils;

final class FileViewModelMapper
{
    public static function fromModel(FileModel $file): FileViewModel
    {
        return new FileViewModel(
            id: $file->id,
            ownerId: $file->ownerId,
            originalName: $file->originalName,
            mimeType: $file->mimeType,
            sizeBytes: $file->sizeBytes,
            status: $file->status,
            createdAt: DateUtils::toAtom($file->createdAt),
            updatedAt: DateUtils::toAtom($file->updatedAt),
        );
    }
}
