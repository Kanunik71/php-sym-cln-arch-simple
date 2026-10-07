<?php

declare(strict_types=1);

namespace App\Application\Service\File\Query;

use App\Application\Mapper\File\FileViewModelMapper;
use App\Application\Model\File\Read\FileViewModel;
use App\Application\Policy\File\FileAccessPolicy;
use App\Application\Port\File\FileRepositoryInterface;

final readonly class FileQueryService
{
    public function __construct(
        private FileAccessPolicy $fileAccessPolicy,
        private FileRepositoryInterface $fileRepository,
    ) {
    }

    public function execute(string $userId, string $fileId): FileViewModel
    {
        $file = $this->fileRepository->findOrFail($fileId);
        $this->fileAccessPolicy->assertUserCanAccess($file, $userId);

        return FileViewModelMapper::fromModel($file);
    }
}
