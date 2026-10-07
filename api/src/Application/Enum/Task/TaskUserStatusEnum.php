<?php

declare(strict_types=1);

namespace App\Application\Enum\Task;

enum TaskUserStatusEnum: string
{
    case Initial = 'Initial';
    case Active = 'Active';
    case Declined = 'Declined';
    case Finished = 'Finished';

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::Initial => $newStatus === self::Active || $newStatus === self::Declined,
            self::Active => $newStatus === self::Declined || $newStatus === self::Finished,
            self::Declined, self::Finished => false,
        };
    }
}
