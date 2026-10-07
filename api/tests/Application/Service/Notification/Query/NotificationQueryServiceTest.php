<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Notification\Query;

use App\Application\Service\Notification\Query\NotificationQueryService;
use App\Application\Enum\Notification\NotificationChannelEnum;
use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Tests\DatabaseTestCase;

final class NotificationQueryServiceTest extends DatabaseTestCase
{
    public function testReturnsPreferences(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $getNotificationPreferences = $this->bed->get(NotificationQueryService::class);

        $result = $getNotificationPreferences->execute(currentUserId: $user->getId());

        $this->assertCount(1, $result);
        $this->assertSame(NotificationTypeEnum::UserUpdated, $result[0]->type);
        $this->assertSame([NotificationChannelEnum::Email, NotificationChannelEnum::Sms], $result[0]->channels);
    }
}
