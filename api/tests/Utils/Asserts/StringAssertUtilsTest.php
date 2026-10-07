<?php

declare(strict_types=1);

namespace App\Tests\Utils\Asserts;

use App\Shared\Utils\Asserts\StringAssertUtils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class StringAssertUtilsTest extends TestCase
{
    public function testNotBlankTrimsValue(): void
    {
        $this->assertSame('Laptop', StringAssertUtils::notBlank('  Laptop  ', 'Asset name'));
    }

    public function testNotBlankRejectsEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Asset name must not be empty.');

        StringAssertUtils::notBlank('   ', 'Asset name');
    }

    public function testMaxLengthAcceptsValueWithinLimit(): void
    {
        $this->assertSame('abc', StringAssertUtils::maxLength('abc', 3, 'Name'));
    }

    public function testMaxLengthRejectsValueOverLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Name must not exceed 3 characters.');

        StringAssertUtils::maxLength('abcd', 3, 'Name');
    }

    public function testNotBlankMaxLengthTrimsAndEnforcesLimit(): void
    {
        $this->assertSame('ab', StringAssertUtils::notBlankMaxLength('  ab  ', 2, 'Name'));
    }

    public function testMaxLengthOrNullPassesNullThrough(): void
    {
        $this->assertNull(StringAssertUtils::maxLengthOrNull(null, 3, 'Name'));
        $this->assertSame('ab', StringAssertUtils::maxLengthOrNull('ab', 3, 'Name'));
    }

    public function testNullIfBlankNormalizesEmptyAndNull(): void
    {
        $this->assertNull(StringAssertUtils::nullIfBlank(null));
        $this->assertNull(StringAssertUtils::nullIfBlank('   '));
        $this->assertSame('user-123', StringAssertUtils::nullIfBlank('  user-123  '));
    }
}
