<?php

declare(strict_types=1);

namespace App\Application\Enum\Task;

enum TaskStatusEnum: string
{
    case Initial = 'Initial';
    case Active = 'Active';
    case Canceled = 'Canceled';
    case Finished = 'Finished';

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::Initial => $newStatus === self::Active,
            self::Active => $newStatus === self::Canceled || $newStatus === self::Finished,
            self::Canceled, self::Finished => false,
        };
    }

    public function keepsAssetInProgress(): bool
    {
        return $this !== self::Canceled && $this !== self::Finished;
    }

    /** @return list<self> */
    public static function thatKeepAssetInProgress(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $status): bool => $status->keepsAssetInProgress(),
        ));
    }
}
