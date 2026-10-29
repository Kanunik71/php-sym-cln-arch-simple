<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing;

final readonly class FileDownloadSignature
{
    public function __construct(
        private string $secret,
    ) {
    }

    public function sign(string $fileId, int $expiresAt): string
    {
        return hash_hmac('sha256', $fileId.'|'.$expiresAt, $this->secret);
    }

    public function matches(string $fileId, ?string $expiresAt, ?string $signature): bool
    {
        if ($expiresAt === null || $signature === null || !ctype_digit($expiresAt)) {
            return false;
        }

        $expiresAtInt = (int) $expiresAt;
        if ($expiresAtInt <= time()) {
            return false;
        }

        $expected = $this->sign($fileId, $expiresAtInt);

        return strlen($signature) === strlen($expected) && hash_equals($expected, $signature);
    }
}
