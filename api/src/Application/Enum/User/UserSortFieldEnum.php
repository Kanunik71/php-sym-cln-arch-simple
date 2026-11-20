<?php

declare(strict_types=1);

namespace App\Application\Enum\User;

use App\Application\Utils\Collection\EnumUtils;

enum UserSortFieldEnum: string
{
    case Email = 'email';
    case Fname = 'fname';
    case Lname = 'lname';
    case City = 'city';
    case CreatedAt = 'createdAt';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        /** @var list<string> $values */
        $values = EnumUtils::caseValues(self::class);

        return $values;
    }
}
