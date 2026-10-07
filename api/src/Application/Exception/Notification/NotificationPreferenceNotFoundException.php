<?php

declare(strict_types=1);

namespace App\Application\Exception\Notification;

use App\Application\Exception\CodedExceptionInterface;
use App\Application\Exception\ErrorCodeEnum;
use App\Application\Enum\Notification\NotificationTypeEnum;
use DomainException;

final class NotificationPreferenceNotFoundException extends DomainException implements CodedExceptionInterface
{
    public static function forOwnerAndType(string $ownerId, NotificationTypeEnum $type): self
    {
        return new self(sprintf(
            'Notification preference "%s" not found for owner "%s".',
            $type->value,
            $ownerId,
        ));
    }

    public function getCodeId(): ErrorCodeEnum
    {
        return ErrorCodeEnum::NotificationPreferenceNotFound;
    }
}
