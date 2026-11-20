<?php

declare(strict_types=1);

namespace App\Tests\Application\Utils\Collection;

use App\Application\Enum\Notification\NotificationChannelEnum;
use App\Application\Utils\Collection\EnumUtils;
use PHPUnit\Framework\TestCase;

final class EnumUtilsTest extends TestCase
{
    public function testValuesMapsBackedEnums(): void
    {
        $this->assertSame(
            ['email', 'sms'],
            EnumUtils::values([NotificationChannelEnum::Email, NotificationChannelEnum::Sms]),
        );
        $this->assertSame([], EnumUtils::values([]));
    }

    public function testCaseValuesReturnsAllCases(): void
    {
        $this->assertSame(
            ['email', 'sms'],
            EnumUtils::caseValues(NotificationChannelEnum::class),
        );
    }
}
