<?php

declare(strict_types=1);

namespace App\Tests\Utils\Asserts;

use App\Shared\Utils\Asserts\NumberAssertUtils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class NumberAssertUtilsTest extends TestCase
{
    public function testMinAcceptsValueAtBoundary(): void
    {
        $this->assertSame(0, NumberAssertUtils::min(0, 0, 'Estimate time'));
        $this->assertSame(10, NumberAssertUtils::min(10, 0, 'Estimate time'));
    }

    public function testMinRejectsValueBelowBoundary(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Estimate time must be 0 or greater.');

        NumberAssertUtils::min(-1, 0, 'Estimate time');
    }

    public function testMinOrNullKeepsNull(): void
    {
        $this->assertNull(NumberAssertUtils::minOrNull(null, 0, 'Estimate time'));
    }
}
