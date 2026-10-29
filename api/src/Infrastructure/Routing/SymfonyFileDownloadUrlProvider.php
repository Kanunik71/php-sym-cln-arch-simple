<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

use App\Application\Port\File\FileDownloadUrlProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class SymfonyFileDownloadUrlProvider implements FileDownloadUrlProviderInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private FileDownloadSignature $signature,
        private int $ttlSeconds,
    ) {
    }

    public function buildDownloadUrl(string $fileId): string
    {
        $expiresAt = time() + $this->ttlSeconds;

        return $this->urlGenerator->generate(
            'files_download',
            [
                'id' => $fileId,
                'exp' => $expiresAt,
                'sig' => $this->signature->sign($fileId, $expiresAt),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    public function buildDownloadUrlOrNull(?string $fileId): ?string
    {
        return $fileId !== null ? $this->buildDownloadUrl($fileId) : null;
    }

    public function matches(string $fileId, ?string $expiresAt, ?string $signature): bool
    {
        return $this->signature->matches($fileId, $expiresAt, $signature);
    }
}
