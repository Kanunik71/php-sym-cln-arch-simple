<?php

declare(strict_types=1);

namespace App\Application\Port\File;

use App\Application\Model\File\FileModel;
use DateTimeImmutable;

interface FileRepositoryInterface
{
    public function save(FileModel $file): FileModel;

    public function findById(string $id): ?FileModel;

    public function findOrFail(string $id): FileModel;

    /**
     * @param list<string> $ids
     *
     * @return list<FileModel>
     */
    public function listByIds(array $ids): array;

    /** @return list<FileModel> */
    public function listExpiredTemporary(DateTimeImmutable $updatedBefore): array;

    /** @return list<FileModel> */
    public function listByOwnerId(string $ownerId): array;

    public function delete(FileModel $file): void;
}
