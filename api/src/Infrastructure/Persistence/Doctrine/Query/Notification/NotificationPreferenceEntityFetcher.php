<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query\Notification;

use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Infrastructure\Persistence\Doctrine\Entity\UserNotificationPreferenceEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;

/**
 * Loads UserNotificationPreferenceEntity root only — userId is a scalar column (no user join).
 */
final readonly class NotificationPreferenceEntityFetcher
{
    private const ALIAS = 'p';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(string $id): ?UserNotificationPreferenceEntity
    {
        /** @var UserNotificationPreferenceEntity|null $entity */
        $entity = $this->baseQuery()
            ->where(self::ALIAS.'.'.UserNotificationPreferenceEntity::FIELD_ID.' = :id')
            ->setParameter('id', Uuid::fromString($id), 'uuid')
            ->getQuery()
            ->getOneOrNullResult();

        return $entity;
    }

    public function findByOwnerIdAndType(string $ownerId, NotificationTypeEnum $type): ?UserNotificationPreferenceEntity
    {
        /** @var UserNotificationPreferenceEntity|null $entity */
        $entity = $this->baseQuery()
            ->andWhere(self::ALIAS.'.'.UserNotificationPreferenceEntity::FIELD_USER_ID.' = :ownerId')
            ->andWhere(self::ALIAS.'.'.UserNotificationPreferenceEntity::FIELD_TYPE.' = :type')
            ->setParameter('ownerId', Uuid::fromString($ownerId), 'uuid')
            ->setParameter('type', $type->value)
            ->getQuery()
            ->getOneOrNullResult();

        return $entity;
    }

    /** @return list<UserNotificationPreferenceEntity> */
    public function listByOwnerId(string $ownerId): array
    {
        /** @var list<UserNotificationPreferenceEntity> $entities */
        $entities = $this->baseQuery()
            ->andWhere(self::ALIAS.'.'.UserNotificationPreferenceEntity::FIELD_USER_ID.' = :ownerId')
            ->setParameter('ownerId', Uuid::fromString($ownerId), 'uuid')
            ->orderBy(self::ALIAS.'.'.UserNotificationPreferenceEntity::FIELD_TYPE, 'ASC')
            ->getQuery()
            ->getResult();

        return $entities;
    }

    private function baseQuery(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select(self::ALIAS)
            ->from(UserNotificationPreferenceEntity::class, self::ALIAS);
    }
}
