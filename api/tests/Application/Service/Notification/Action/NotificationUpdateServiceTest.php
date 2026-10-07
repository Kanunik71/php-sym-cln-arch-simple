<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Notification\Action;

use App\Application\Service\Notification\Action\NotificationUpdateService;
use App\Application\Model\Notification\Read\NotificationPreferenceViewModel;
use App\Application\Model\Notification\Action\UpdateNotificationPreferencesModel;
use App\Application\Enum\Notification\NotificationChannelEnum;
use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Application\Exception\Notification\NotificationPreferenceNotFoundException;
use App\Tests\DatabaseTestCase;
use InvalidArgumentException;

final class NotificationUpdateServiceTest extends DatabaseTestCase
{
    public function testUpdatesPreferences(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $updateNotificationPreferences = $this->bed->get(NotificationUpdateService::class);

        $result = $updateNotificationPreferences->execute(
            model: new UpdateNotificationPreferencesModel([
                new NotificationPreferenceViewModel(
                    type: NotificationTypeEnum::UserUpdated,
                    enabled: true,
                    channels: [NotificationChannelEnum::Email],
                ),
            ]),
            currentUserId: $user->getId(),
        );

        $this->assertCount(1, $result);
        $this->assertSame(NotificationTypeEnum::UserUpdated, $result[0]->type);
        $this->assertTrue($result[0]->enabled);
        $this->assertSame([NotificationChannelEnum::Email], $result[0]->channels);

        $persisted = $this->bed->findNotificationPreference($user->getId(), NotificationTypeEnum::UserUpdated);
        $this->assertNotNull($persisted);
        $this->assertTrue($persisted->isEmailEnabled());
        $this->assertFalse($persisted->isSmsEnabled());
    }

    public function testThrowsWhenPreferenceMissing(): void
    {
        $user = $this->bed->createUser(
            email: 'john@example.com',
            withDefaultNotificationPreferences: false,
        );
        $updateNotificationPreferences = $this->bed->get(NotificationUpdateService::class);

        $this->expectException(NotificationPreferenceNotFoundException::class);

        $updateNotificationPreferences->execute(
            model: new UpdateNotificationPreferencesModel([
                new NotificationPreferenceViewModel(
                    type: NotificationTypeEnum::UserUpdated,
                    enabled: false,
                    channels: [],
                ),
            ]),
            currentUserId: $user->getId(),
        );
    }

    public function testThrowsWhenTypeIsTransactional(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $updateNotificationPreferences = $this->bed->get(NotificationUpdateService::class);

        $this->expectException(InvalidArgumentException::class);

        $updateNotificationPreferences->execute(
            model: new UpdateNotificationPreferencesModel([
                new NotificationPreferenceViewModel(
                    type: NotificationTypeEnum::UserRegistered,
                    enabled: false,
                    channels: [],
                ),
            ]),
            currentUserId: $user->getId(),
        );
    }
}
