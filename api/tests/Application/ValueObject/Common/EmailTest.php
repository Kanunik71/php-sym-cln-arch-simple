<?php

declare(strict_types=1);

namespace App\Tests\Application\ValueObject\Common;

use App\Application\ValueObject\Common\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testNormalizesAndAcceptsValidEmail(): void
    {
        $email = new Email('  John.Doe@Example.COM ');

        $this->assertSame('john.doe@example.com', $email->toString());
        $this->assertSame('john.doe@example.com', (string) $email);
    }

    public function testEqualsComparesNormalizedValue(): void
    {
        $left = new Email('a@example.com');
        $right = new Email('A@Example.com');

        $this->assertTrue($left->equals($right));
    }

    public function testRejectsBlankEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email must not be empty.');

        new Email('   ');
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email is invalid.');

        new Email('not-an-email');
    }

    public function testRejectsEmailExceedingMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Email must not exceed %d characters.', Email::MAX_LENGTH));

        new Email(str_repeat('a', Email::MAX_LENGTH - 11).'@example.com');
    }
}
