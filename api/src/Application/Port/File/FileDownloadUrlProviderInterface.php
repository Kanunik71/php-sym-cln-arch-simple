<?php

declare(strict_types=1);

namespace App\Application\Port\File;

interface FileDownloadUrlProviderInterface
{
    public function buildDownloadUrl(string $fileId): string;

    public function buildDownloadUrlOrNull(?string $fileId): ?string;
}
