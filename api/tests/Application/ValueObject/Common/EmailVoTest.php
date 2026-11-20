<?php

declare(strict_types=1);

namespace App\Tests\Application\ValueObject\Common;

use App\Application\ValueObject\Common\EmailVo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmailVoTest extends TestCase
{
    public function testNormalizesAndAcceptsValidEmail(): void
    {
        $email = new EmailVo('  John.Doe@Example.COM ');

        $this->assertSame('john.doe@example.com', $email->toString());
        $this->assertSame('john.doe@example.com', (string) $email);
    }

    public function testEqualsComparesNormalizedValue(): void
    {
        $left = new EmailVo('a@example.com');
        $right = new EmailVo('A@Example.com');

        $this->assertTrue($left->equals($right));
    }

    public function testRejectsBlankEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email must not be empty.');

        new EmailVo('   ');
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is invalid.');

        new EmailVo('not-an-email');
    }

    public function testRejectsEmailExceedingMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Email must not exceed %d characters.', EmailVo::MAX_LENGTH));

        new EmailVo(str_repeat('a', EmailVo::MAX_LENGTH - 11).'@example.com');
    }
}
