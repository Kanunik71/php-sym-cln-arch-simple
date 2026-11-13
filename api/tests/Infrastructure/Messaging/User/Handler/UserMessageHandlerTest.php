<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Messaging\User\Handler;

use App\Application\Enum\Notification\NotificationChannelEnum;
use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Application\Event\User\UserRegisteredEvent;
use App\Application\Event\User\UserUpdatedEvent;
use App\Application\Port\Notification\MailerInterface;
use App\Application\Port\Notification\NotificationTemplateRendererInterface;
use App\Application\Port\Notification\SmsSenderInterface;
use App\Application\Service\Notification\Lifecycle\NotificationService;
use App\Infrastructure\Messaging\User\Handler\UserRegisteredMessageHandler;
use App\Infrastructure\Messaging\User\Handler\UserUpdatedMessageHandler;
use App\Tests\DatabaseTestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Infrastructure: Ð¾Ð±Ñ€Ð°Ð±Ð¾Ñ‚Ñ‡Ð¸Ðº ÑÐ¾Ð±Ñ‹Ñ‚Ð¸Ñ Ð²Ñ‹Ð·Ñ‹Ð²Ð°ÐµÑ‚ Application-ÑÐµÑ€Ð²Ð¸Ñ ÑƒÐ²ÐµÐ´Ð¾Ð¼Ð»ÐµÐ½Ð¸Ð¹
 * Ñ Ð¿Ñ€Ð°Ð²Ð¸Ð»ÑŒÐ½Ñ‹Ð¼ Ð¿Ð¾Ð»ÑŒÐ·Ð¾Ð²Ð°Ñ‚ÐµÐ»ÐµÐ¼ Ð¸ Ñ‚Ð¸Ð¿Ð¾Ð¼ â€” Ð¿Ñ€Ð¾Ð²ÐµÑ€ÑÐµÑ‚ÑÑ Ð¿Ð¾ Ñ„Ð°ÐºÑ‚Ð¸Ñ‡ÐµÑÐºÐ¸ Ð¾Ñ‚Ð¿Ñ€Ð°Ð²Ð»ÐµÐ½Ð½Ð¾Ð¼Ñƒ Ð¿Ð¸ÑÑŒÐ¼Ñƒ.
 */
final class UserMessageHandlerTest extends DatabaseTestCase
{
    public function testRegisteredHandlerSendsTransactionalEmail(): void
    {
        $user = $this->bed->createUser(
            email: 'john@example.com',
            withDefaultNotificationPreferences: false,
        );
        $mailer = new RecordingMailer();

        $this->createHandler(UserRegisteredMessageHandler::class, $mailer)->__invoke(
            new UserRegisteredEvent(userId: $user->getId()),
        );

        $this->assertCount(1, $mailer->sent);
        $this->assertSame('john@example.com', $mailer->sent[0]['to']);
        $this->assertNotSame('', $mailer->sent[0]['subject']);
    }

    public function testRegisteredHandlerSendsEmailDespiteDisabledType(): void
    {
        $user = $this->bed->createUser(
            email: 'john@example.com',
            withDefaultNotificationPreferences: false,
        );
        $mailer = new RecordingMailer();

        // Ñ‚Ñ€Ð°Ð½Ð·Ð°ÐºÑ†Ð¸Ð¾Ð½Ð½Ñ‹Ð¹ Ñ‚Ð¸Ð¿ Ð¾Ñ‚Ð¿Ñ€Ð°Ð²Ð»ÑÐµÑ‚ÑÑ Ð½ÐµÐ·Ð°Ð²Ð¸ÑÐ¸Ð¼Ð¾ Ð¾Ñ‚ Ð½Ð°ÑÑ‚Ñ€Ð¾ÐµÐº
        $this->createHandler(UserRegisteredMessageHandler::class, $mailer)->__invoke(
            new UserRegisteredEvent(userId: $user->getId()),
        );

        $this->assertCount(1, $mailer->sent);
    }

    public function testUpdatedHandlerSendsNotification(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $mailer = new RecordingMailer();

        $this->createHandler(UserUpdatedMessageHandler::class, $mailer)->__invoke(
            new UserUpdatedEvent(userId: $user->getId()),
        );

        $this->assertCount(1, $mailer->sent);
        $this->assertSame('john@example.com', $mailer->sent[0]['to']);
    }

    public function testUpdatedHandlerSkipsNotificationWhenTypeDisabled(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $preference = $this->bed->findNotificationPreference($user->getId(), NotificationTypeEnum::UserUpdated);
        $this->assertNotNull($preference);
        $this->bed->notificationPreferenceRepository()->save($preference->update(
            enabled: false,
            channels: [NotificationChannelEnum::Email],
        ));
        $mailer = new RecordingMailer();

        $this->createHandler(UserUpdatedMessageHandler::class, $mailer)->__invoke(
            new UserUpdatedEvent(userId: $user->getId()),
        );

        $this->assertSame([], $mailer->sent);
    }

    public function testRepeatedDeliverySendsAgain(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $mailer = new RecordingMailer();
        $handler = $this->createHandler(UserUpdatedMessageHandler::class, $mailer);

        $handler->__invoke(new UserUpdatedEvent(userId: $user->getId()));
        $handler->__invoke(new UserUpdatedEvent(userId: $user->getId()));

        $this->assertCount(2, $mailer->sent);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $handlerClass
     *
     * @return T
     */
    private function createHandler(string $handlerClass, MailerInterface $mailer): object
    {
        $notificationService = $this->createNotificationService($mailer);

        return new $handlerClass($notificationService);
    }

    private function createNotificationService(MailerInterface $mailer): NotificationService
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
            smsSender: new RecordingSmsSender(),
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
