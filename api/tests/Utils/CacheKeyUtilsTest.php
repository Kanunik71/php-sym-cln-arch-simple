<?php

declare(strict_types=1);

namespace App\Tests\Utils;

use App\Shared\Utils\CacheKeyUtils;
use PHPUnit\Framework\TestCase;

final class CacheKeyUtilsTest extends TestCase
{
    public function testFingerprintIdsIsOrderInsensitive(): void
    {
        $this->assertSame(
            CacheKeyUtils::fingerprintIds(['b', 'a', 'c']),
            CacheKeyUtils::fingerprintIds(['c', 'a', 'b']),
        );
    }

    public function testFingerprintIdsIgnoresDuplicates(): void
    {
        $this->assertSame(
            CacheKeyUtils::fingerprintIds(['a', 'b']),
            CacheKeyUtils::fingerprintIds(['a', 'a', 'b', 'b']),
        );
    }

    public function testFingerprintIdsIsSha256OfSortedJoinedIds(): void
    {
        $this->assertSame(
            hash('sha256', 'a,b,c'),
            CacheKeyUtils::fingerprintIds(['c', 'a', 'b']),
        );
    }

    public function testFingerprintIdsForEmptyList(): void
    {
        $this->assertSame(hash('sha256', ''), CacheKeyUtils::fingerprintIds([]));
    }
}
