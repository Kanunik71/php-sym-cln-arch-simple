<?php

declare(strict_types=1);

namespace App\Application\Service\Notification\Query;

use App\Application\Mapper\Notification\NotificationPreferenceViewModelMapper;
use App\Application\Model\Notification\Read\NotificationPreferenceViewModel;
use App\Application\Port\Notification\NotificationPreferenceRepositoryInterface;

final readonly class NotificationQueryService
{
    public function __construct(
        private NotificationPreferenceRepositoryInterface $preferenceRepository,
    ) {
    }

    /** @return list<NotificationPreferenceViewModel> */
    public function execute(string $currentUserId): array
    {
        return NotificationPreferenceViewModelMapper::fromModelList(
            $this->preferenceRepository->listByOwnerId($currentUserId),
        );
    }
}
