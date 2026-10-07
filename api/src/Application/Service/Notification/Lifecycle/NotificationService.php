<?php

declare(strict_types=1);

namespace App\Application\Service\Notification\Lifecycle;

use App\Application\Port\Notification\MailerInterface;
use App\Application\Port\Notification\NotificationPreferenceRepositoryInterface;
use App\Application\Port\Notification\NotificationTemplateRendererInterface;
use App\Application\Port\Notification\SmsSenderInterface;
use App\Application\Port\User\UserRepositoryInterface;
use App\Application\Enum\Notification\NotificationChannelEnum;
use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Application\Model\User\UserModel;
use Psr\Log\LoggerInterface;

final readonly class NotificationService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private NotificationPreferenceRepositoryInterface $preferenceRepository,
        private NotificationTemplateRendererInterface $templateRenderer,
        private MailerInterface $mailer,
        private SmsSenderInterface $smsSender,
        private LoggerInterface $logger,
    ) {
    }

    public function notify(string $userId, NotificationTypeEnum $type): void
    {
        $user = $this->userRepository->findOrFail($userId);
        $channels = $this->resolveChannels($user, $type);

        if ($channels === []) {
            return;
        }

        $vars = [
            'fname' => $user->getFname() ?? '',
            'lname' => $user->getLname() ?? '',
            'email' => $user->getEmail()->toString(),
        ];

        foreach ($channels as $channel) {
            $rendered = $this->templateRenderer->render($type, $channel, $vars);

            match ($channel) {
                NotificationChannelEnum::Email => $this->mailer->send(
                    to: $user->getEmail()->toString(),
                    subject: $rendered->subject ?? '',
                    body: $rendered->body,
                ),
                NotificationChannelEnum::Sms => $this->sendSms($user, $rendered->body),
            };
        }
    }

    /** @return list<NotificationChannelEnum> */
    private function resolveChannels(UserModel $user, NotificationTypeEnum $type): array
    {
        if ($type->isTransactional()) {
            return [NotificationChannelEnum::Email];
        }

        $preference = $this->preferenceRepository->findByOwnerIdAndType($user->getId(), $type);

        if ($preference === null || !$preference->isEnabled()) {
            return [];
        }

        $channels = [];

        foreach ($preference->getChannels() as $channel) {
            if ($channel === NotificationChannelEnum::Sms && $user->getPhone() === null) {
                $this->logger->warning('Skipping SMS notification: user has no phone.', [
                    'userId' => $user->getId(),
                    'type' => $type->value,
                ]);

                continue;
            }

            $channels[] = $channel;
        }

        return $channels;
    }

    private function sendSms(UserModel $user, string $body): void
    {
        $phone = $user->getPhone();

        if ($phone === null) {
            return;
        }

        $this->smsSender->send($phone->toString(), $body);
    }
}
