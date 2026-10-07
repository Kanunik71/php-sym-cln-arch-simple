<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

use App\Application\Port\File\FileDownloadUrlProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class SymfonyFileDownloadUrlProvider implements FileDownloadUrlProviderInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function buildDownloadUrl(string $fileId): string
    {
        return $this->urlGenerator->generate(
            'files_download',
            ['id' => $fileId],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    public function buildDownloadUrlOrNull(?string $fileId): ?string
    {
        return $fileId !== null ? $this->buildDownloadUrl($fileId) : null;
    }
}
