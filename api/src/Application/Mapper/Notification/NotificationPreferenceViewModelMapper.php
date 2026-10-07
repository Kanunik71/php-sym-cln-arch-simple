<?php

declare(strict_types=1);

namespace App\Application\Mapper\Notification;

use App\Application\Model\Notification\NotificationPreferenceModel;
use App\Application\Model\Notification\Read\NotificationPreferenceViewModel;

final class NotificationPreferenceViewModelMapper
{
    public static function fromModel(NotificationPreferenceModel $preference): NotificationPreferenceViewModel
    {
        return new NotificationPreferenceViewModel(
            type: $preference->type,
            enabled: $preference->enabled,
            channels: $preference->getChannels(),
        );
    }

    /**
     * @param list<NotificationPreferenceModel> $preferences
     *
     * @return list<NotificationPreferenceViewModel>
     */
    public static function fromModelList(array $preferences): array
    {
        return array_map(
            self::fromModel(...),
            $preferences,
        );
    }
}
