<?php

declare(strict_types=1);

namespace App\Application\Model\File\Read;

final readonly class DownloadFileModel
{
    public function __construct(
        public string $originalName,
        public string $mimeType,
        /** @var resource */
        public mixed $stream,
    ) {
    }
}
