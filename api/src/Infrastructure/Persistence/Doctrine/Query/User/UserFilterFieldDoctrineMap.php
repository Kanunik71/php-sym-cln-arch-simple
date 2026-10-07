<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query\User;

use App\Application\Enum\User\UserFilterFieldEnum;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use LogicException;

/**
 * Maps Application filter field whitelist to Doctrine entity paths.
 * Keep DB/ORM details out of Application Enum.
 */
final class UserFilterFieldDoctrineMap
{
    public static function path(UserFilterFieldEnum $field, string $alias = 'u'): string
    {
        return $alias.'.'.match ($field) {
            UserFilterFieldEnum::Email => UserEntity::FIELD_EMAIL,
            UserFilterFieldEnum::Fname => UserEntity::FIELD_FNAME,
            UserFilterFieldEnum::Lname => UserEntity::FIELD_LNAME,
            UserFilterFieldEnum::City => UserEntity::FIELD_CITY,
            UserFilterFieldEnum::CreatedAt => UserEntity::FIELD_CREATED_AT,
            UserFilterFieldEnum::TaskId => throw new LogicException('Filter field "taskId" has no direct Doctrine path; use UserTaskIdFilterApplier.'),
        };
    }
}
