<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Template;

use App\Application\Model\Notification\RenderedNotificationModel;
use App\Application\Port\Notification\NotificationTemplateRendererInterface;
use App\Application\Enum\Notification\NotificationChannelEnum;
use App\Application\Enum\Notification\NotificationTypeEnum;
use InvalidArgumentException;
use Twig\Environment;

final readonly class TwigNotificationTemplateRenderer implements NotificationTemplateRendererInterface
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    public function render(
        NotificationTypeEnum $type,
        NotificationChannelEnum $channel,
        array $vars,
    ): RenderedNotificationModel {
        $templateName = sprintf('notification/%s.%s.twig', $type->value, $channel->value);

        try {
            $template = $this->twig->load($templateName);
        } catch (\Twig\Error\LoaderError $exception) {
            throw new InvalidArgumentException(sprintf('Notification template "%s" not found.', $templateName), 0, $exception);
        }

        $body = $template->hasBlock('body')
            ? $template->renderBlock('body', $vars)
            : $template->render($vars);

        $subject = $template->hasBlock('subject')
            ? trim($template->renderBlock('subject', $vars))
            : null;

        return new RenderedNotificationModel(
            body: trim($body),
            subject: $subject !== '' ? $subject : null,
        );
    }
}
