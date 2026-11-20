<?php

declare(strict_types=1);

namespace App\Tests\Application\ValueObject\Common;

use App\Application\ValueObject\Common\MoneyVo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyVoTest extends TestCase
{
    public function testRoundsToTwoDecimalPlaces(): void
    {
        $this->assertSame(10.13, (new MoneyVo(10.129))->toFloat());
        $this->assertSame(0.0, (new MoneyVo(0))->toFloat());
    }

    public function testStringRoundTripMatchesDecimalColumn(): void
    {
        $this->assertSame('999.99', (new MoneyVo(999.99))->toString());
        $this->assertSame('2500.00', MoneyVo::fromString('2500')->toString());
        $this->assertSame(2500.0, MoneyVo::fromString('2500.00')->toFloat());
        $this->assertSame('999.99', (string) (new MoneyVo(999.99)));
    }

    public function testEqualsComparesNormalizedValue(): void
    {
        $this->assertTrue((new MoneyVo(10.129))->equals(new MoneyVo(10.13)));
        $this->assertFalse((new MoneyVo(10.12))->equals(new MoneyVo(10.13)));
    }

    public function testRejectsNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be a non-negative number with up to 2 decimal places.');

        new MoneyVo(-0.01);
    }

    public function testRejectsAmountAboveMax(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must not exceed 99999999.99.');

        new MoneyVo(MoneyVo::MAX_AMOUNT + 0.01);
    }

    public function testAcceptsMaxAmount(): void
    {
        $this->assertSame(MoneyVo::MAX_AMOUNT, (new MoneyVo(MoneyVo::MAX_AMOUNT))->toFloat());
    }
}
