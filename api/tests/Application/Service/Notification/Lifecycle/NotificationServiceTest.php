<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\Notification\Lifecycle;

use App\Application\Port\Notification\MailerInterface;
use App\Application\Port\Notification\NotificationTemplateRendererInterface;
use App\Application\Port\Notification\SmsSenderInterface;
use App\Application\Service\Notification\Lifecycle\NotificationService;
use App\Application\Enum\Notification\NotificationChannelEnum;
use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Tests\DatabaseTestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

final class NotificationServiceTest extends DatabaseTestCase
{
    public function testUserRegisteredAlwaysSendsEmail(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com', withDefaultNotificationPreferences: false);
        $mailer = new RecordingMailer();
        $sms = new RecordingSmsSender();

        $this->createService($mailer, $sms)->notify($user->getId(), NotificationTypeEnum::UserRegistered);

        $this->assertCount(1, $mailer->sent);
        $this->assertSame('john@example.com', $mailer->sent[0]['to']);
        $this->assertNotSame('', $mailer->sent[0]['subject']);
        $this->assertSame([], $sms->sent);
    }

    public function testUserUpdatedRespectsPreferencesAndSkipsSmsWithoutPhone(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $mailer = new RecordingMailer();
        $sms = new RecordingSmsSender();

        $this->createService($mailer, $sms)->notify($user->getId(), NotificationTypeEnum::UserUpdated);

        $this->assertCount(1, $mailer->sent);
        $this->assertSame([], $sms->sent);
    }

    public function testUserUpdatedSendsSmsWhenPhonePresent(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com', phone: '+79001234567');
        $mailer = new RecordingMailer();
        $sms = new RecordingSmsSender();

        $this->createService($mailer, $sms)->notify($user->getId(), NotificationTypeEnum::UserUpdated);

        $this->assertCount(1, $mailer->sent);
        $this->assertCount(1, $sms->sent);
        $this->assertSame('+79001234567', $sms->sent[0]['to']);
    }

    public function testUserUpdatedSkipsWhenDisabled(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com', phone: '+79001234567');
        $preference = $this->bed->findNotificationPreference($user->getId(), NotificationTypeEnum::UserUpdated);
        $this->assertNotNull($preference);
        $this->bed->notificationPreferenceRepository()->save($preference->update(
            enabled: false,
            channels: [NotificationChannelEnum::Email, NotificationChannelEnum::Sms],
        ));

        $mailer = new RecordingMailer();
        $sms = new RecordingSmsSender();

        $this->createService($mailer, $sms)->notify($user->getId(), NotificationTypeEnum::UserUpdated);

        $this->assertSame([], $mailer->sent);
        $this->assertSame([], $sms->sent);
    }

    private function createService(MailerInterface $mailer, SmsSenderInterface $sms): NotificationService
    {
        $base = $this->bed->get(NotificationService::class);
        $ref = new ReflectionClass($base);

        /** @var NotificationTemplateRendererInterface $renderer */
        $renderer = $ref->getProperty('templateRenderer')->getValue($base);
        /** @var LoggerInterface $logger */
        $logger = $ref->getProperty('logger')->getValue($base);

        return new NotificationService(
            userRepository: $this->bed->userRepository(),
            preferenceRepository: $this->bed->notificationPreferenceRepository(),
            templateRenderer: $renderer,
            mailer: $mailer,
            smsSender: $sms,
            logger: $logger,
        );
    }
}

final class RecordingMailer implements MailerInterface
{
    /** @var list<array{to: string, subject: string, body: string}> */
    public array $sent = [];

    public function send(string $to, string $subject, string $body): void
    {
        $this->sent[] = ['to' => $to, 'subject' => $subject, 'body' => $body];
    }
}

final class RecordingSmsSender implements SmsSenderInterface
{
    /** @var list<array{to: string, body: string}> */
    public array $sent = [];

    public function send(string $to, string $body): void
    {
        $this->sent[] = ['to' => $to, 'body' => $body];
    }
}
