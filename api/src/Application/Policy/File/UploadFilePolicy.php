<?php

declare(strict_types=1);

namespace App\Application\Policy\File;

use InvalidArgumentException;

final readonly class UploadFilePolicy
{
    /** @param list<string> $allowedMimeTypes */
    public function __construct(
        private int $maxUploadBytes,
        private array $allowedMimeTypes,
    ) {
    }

    public function assertValidUpload(string $mimeType, int $sizeBytes): void
    {
        if ($sizeBytes <= 0) {
            throw new InvalidArgumentException('Uploaded file is empty.');
        }

        if ($sizeBytes > $this->maxUploadBytes) {
            throw new InvalidArgumentException(sprintf('Uploaded file exceeds maximum size of %d bytes.', $this->maxUploadBytes));
        }

        if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
            throw new InvalidArgumentException(sprintf('Mime type "%s" is not allowed.', $mimeType));
        }
    }
}
