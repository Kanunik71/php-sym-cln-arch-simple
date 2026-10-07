<?php

declare(strict_types=1);

namespace App\Application\Policy\File;

use App\Application\Exception\File\UnauthorizedFileAccessException;
use App\Application\Model\File\FileModel;

final readonly class FileAccessPolicy
{
    public function assertUserCanAccess(FileModel $file, string $userId): void
    {
        if ($file->ownerId !== $userId) {
            throw UnauthorizedFileAccessException::forUser($file->id, $userId);
        }
    }
}
