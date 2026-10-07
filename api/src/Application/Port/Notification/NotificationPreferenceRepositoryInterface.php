<?php

declare(strict_types=1);

namespace App\Application\Port\Notification;

use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Application\Model\Notification\NotificationPreferenceModel;

interface NotificationPreferenceRepositoryInterface
{
    public function save(NotificationPreferenceModel $preference): NotificationPreferenceModel;

    public function findByOwnerIdAndType(string $ownerId, NotificationTypeEnum $type): ?NotificationPreferenceModel;

    /** @return list<NotificationPreferenceModel> */
    public function listByOwnerId(string $ownerId): array;

    public function delete(NotificationPreferenceModel $preference): void;
}
