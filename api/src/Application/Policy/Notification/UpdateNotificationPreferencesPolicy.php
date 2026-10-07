<?php

declare(strict_types=1);

namespace App\Application\Policy\Notification;

use App\Application\Model\Notification\Read\NotificationPreferenceViewModel;
use App\Application\Model\Notification\Action\UpdateNotificationPreferencesModel;
use InvalidArgumentException;

final readonly class UpdateNotificationPreferencesPolicy
{
    public function assertConfigurableTypes(UpdateNotificationPreferencesModel $model): void
    {
        foreach ($model->preferences as $item) {
            $this->assertItemConfigurable($item);
        }
    }

    private function assertItemConfigurable(NotificationPreferenceViewModel $item): void
    {
        if ($item->type->isTransactional()) {
            throw new InvalidArgumentException(sprintf(
                'Notification type "%s" is transactional and cannot be configured.',
                $item->type->value,
            ));
        }
    }
}
