<?php

declare(strict_types=1);

namespace App\Tests\Utils;

use App\Shared\Utils\DateUtils;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DateUtilsTest extends TestCase
{
    public function testToAtomFormatsAsIso8601(): void
    {
        $date = new DateTimeImmutable('2026-08-18T13:37:00+03:00');

        $this->assertSame('2026-08-18T13:37:00+03:00', DateUtils::toAtom($date));
    }

    public function testToAtomOrNullReturnsNullForNullInput(): void
    {
        $this->assertNull(DateUtils::toAtomOrNull(null));
    }

    public function testToAtomOrNullFormatsDate(): void
    {
        $date = new DateTimeImmutable('2026-08-18T10:00:00Z');

        $this->assertSame('2026-08-18T10:00:00+00:00', DateUtils::toAtomOrNull($date));
    }
}
