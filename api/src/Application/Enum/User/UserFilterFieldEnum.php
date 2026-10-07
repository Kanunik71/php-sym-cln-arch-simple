<?php

declare(strict_types=1);

namespace App\Application\Enum\User;

use App\Application\Enum\Common\FilterFieldTypeEnum;
use App\Application\Enum\Common\FilterOperatorEnum;
use App\Application\Model\Common\Query\FilterFieldMetaInterface;

enum UserFilterFieldEnum: string implements FilterFieldMetaInterface
{
    case Email = 'email';
    case Fname = 'fname';
    case Lname = 'lname';
    case City = 'city';
    case CreatedAt = 'createdAt';
    case TaskId = 'taskId';

    public function type(): FilterFieldTypeEnum
    {
        return match ($this) {
            self::CreatedAt => FilterFieldTypeEnum::DateTime,
            default => FilterFieldTypeEnum::String,
        };
    }

    /** @return list<FilterOperatorEnum> */
    public function operators(): array
    {
        return match ($this) {
            self::TaskId => [FilterOperatorEnum::Eq, FilterOperatorEnum::In],
            default => $this->type()->operators(),
        };
    }
}
