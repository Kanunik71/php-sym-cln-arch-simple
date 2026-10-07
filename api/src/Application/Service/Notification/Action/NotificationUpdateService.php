<?php

declare(strict_types=1);

namespace App\Application\Service\Notification\Action;

use App\Application\Exception\Notification\NotificationPreferenceNotFoundException;
use App\Application\Mapper\Notification\NotificationPreferenceViewModelMapper;
use App\Application\Model\Notification\Read\NotificationPreferenceViewModel;
use App\Application\Model\Notification\Action\UpdateNotificationPreferencesModel;
use App\Application\Policy\Notification\UpdateNotificationPreferencesPolicy;
use App\Application\Port\Notification\NotificationPreferenceRepositoryInterface;

final readonly class NotificationUpdateService
{
    public function __construct(
        private NotificationPreferenceRepositoryInterface $preferenceRepository,
        private UpdateNotificationPreferencesPolicy $policy,
    ) {
    }

    /** @return list<NotificationPreferenceViewModel> */
    public function execute(UpdateNotificationPreferencesModel $model, string $currentUserId): array
    {
        $this->policy->assertConfigurableTypes($model);

        foreach ($model->preferences as $item) {
            $existing = $this->preferenceRepository->findByOwnerIdAndType(
                $currentUserId,
                $item->type,
            );

            if ($existing === null) {
                throw NotificationPreferenceNotFoundException::forOwnerAndType($currentUserId, $item->type);
            }

            $this->preferenceRepository->save($existing->update(
                enabled: $item->enabled,
                channels: $item->channels,
            ));
        }

        return NotificationPreferenceViewModelMapper::fromModelList(
            $this->preferenceRepository->listByOwnerId($currentUserId),
        );
    }
}
