<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query\User;

use App\Application\Enum\User\UserSortFieldEnum;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;

/**
 * Maps Application sort field whitelist to Doctrine entity paths.
 * Keep DB/ORM details out of Application Enum.
 */
final class UserSortFieldDoctrineMap
{
    public static function path(UserSortFieldEnum $field, string $alias = 'u'): string
    {
        return $alias.'.'.match ($field) {
            UserSortFieldEnum::Email => UserEntity::FIELD_EMAIL,
            UserSortFieldEnum::Fname => UserEntity::FIELD_FNAME,
            UserSortFieldEnum::Lname => UserEntity::FIELD_LNAME,
            UserSortFieldEnum::City => UserEntity::FIELD_CITY,
            UserSortFieldEnum::CreatedAt => UserEntity::FIELD_CREATED_AT,
        };
    }
}
