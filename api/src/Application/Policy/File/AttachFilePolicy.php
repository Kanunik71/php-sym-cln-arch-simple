<?php

declare(strict_types=1);

namespace App\Application\Policy\File;

use App\Application\Exception\File\UnauthorizedFileAccessException;
use App\Application\Model\File\FileModel;

final readonly class AttachFilePolicy
{
    public function assertCanAttach(FileModel $file, string $userId): void
    {
        if ($file->ownerId !== $userId) {
            throw UnauthorizedFileAccessException::forUser($file->id, $userId);
        }

        // Temporary (first attach) and Permanent (re-attach / already linked) are both allowed.
    }
}
