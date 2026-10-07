<?php

declare(strict_types=1);

namespace App\Tests\Utils;

use App\Shared\Utils\MoneyUtils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyUtilsTest extends TestCase
{
    public function testNormalizeRoundsToTwoDecimalPlaces(): void
    {
        $this->assertSame(10.13, MoneyUtils::normalize(10.129));
        $this->assertSame(0.0, MoneyUtils::normalize(0));
    }

    public function testToStringAndFromStringRoundTrip(): void
    {
        $this->assertSame('999.99', MoneyUtils::toString(999.99));
        $this->assertSame(2500.0, MoneyUtils::fromString('2500.00'));
    }

    public function testNormalizeRejectsNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MoneyUtils::normalize(-0.01);
    }

    public function testNormalizeRejectsAmountAboveMax(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must not exceed 99999999.99.');

        MoneyUtils::normalize(MoneyUtils::MAX_AMOUNT + 0.01);
    }

    public function testNormalizeAcceptsMaxAmount(): void
    {
        $this->assertSame(MoneyUtils::MAX_AMOUNT, MoneyUtils::normalize(MoneyUtils::MAX_AMOUNT));
    }
}
