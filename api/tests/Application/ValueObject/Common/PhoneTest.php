<?php

declare(strict_types=1);

namespace App\Tests\Application\ValueObject\Common;

use App\Application\ValueObject\Common\Phone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PhoneTest extends TestCase
{
    public function testAcceptsE164(): void
    {
        $phone = new Phone('+79001234567');

        $this->assertSame('+79001234567', $phone->toString());
    }

    public function testNormalizesFormatting(): void
    {
        $phone = new Phone('+7 (900) 123-45-67');

        $this->assertSame('+79001234567', $phone->toString());
    }

    public function testRejectsInvalidPhone(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Phone('89001234567');
    }
}
