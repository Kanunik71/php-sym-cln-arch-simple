<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Routing;

use App\Infrastructure\Routing\FileDownloadSignature;
use PHPUnit\Framework\TestCase;

final class FileDownloadSignatureTest extends TestCase
{
    public function testMatchesOnlyUnexpiredSignatureForTheSameFile(): void
    {
        $signature = new FileDownloadSignature('test-secret');
        $expiresAt = time() + 60;
        $signed = $signature->sign('file-1', $expiresAt);

        $this->assertTrue($signature->matches('file-1', (string) $expiresAt, $signed));
        $this->assertFalse($signature->matches('file-2', (string) $expiresAt, $signed));

        $expiredAt = time() - 5;
        $expired = $signature->sign('file-1', $expiredAt);
        $this->assertFalse($signature->matches('file-1', (string) $expiredAt, $expired));
    }
}
