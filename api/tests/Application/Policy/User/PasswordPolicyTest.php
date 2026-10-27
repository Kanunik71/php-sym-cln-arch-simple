<?php

declare(strict_types=1);

namespace App\Tests\Application\Policy\User;

use App\Application\Policy\User\PasswordPolicy;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PasswordPolicyTest extends TestCase
{
    public function testAcceptsMinimumLength(): void
    {
        (new PasswordPolicy())->assertValid('123456');

        $this->addToAssertionCount(1);
    }

    public function testAcceptsMaximumLength(): void
    {
        (new PasswordPolicy())->assertValid(str_repeat('a', PasswordPolicy::MAX_LENGTH));

        $this->addToAssertionCount(1);
    }

    public function testRejectsTooShortPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PasswordPolicy())->assertValid('12345');
    }

    public function testRejectsTooLongPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PasswordPolicy())->assertValid(str_repeat('a', PasswordPolicy::MAX_LENGTH + 1));
    }

    public function testDoesNotTrimPassword(): void
    {
        (new PasswordPolicy())->assertValid(' 12345 ');

        $this->addToAssertionCount(1);
    }
}
