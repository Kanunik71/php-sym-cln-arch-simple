<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query\Task;

use App\Application\Model\Common\PaginationModel;
use App\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\TaskUserEntity;
use App\Infrastructure\Persistence\Doctrine\Query\Common\DoctrinePaginationApplier;
use App\Shared\Utils\UidUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;

/**
 * Loads TaskEntity with the associations the caller needs (no lazy follow-up for Mapper).
 *
 * - Light (`findById` / `listByIds` / `listByUserId` / `listByOwnerId`): root only — parentId and ownerId are scalar columns.
 * - View (`findViewById` / `listViewPageByUserId`): taskUsers (userId scalar on TaskUser).
 * Inverse children / assets / taskGroups are not loaded here.
 */
final readonly class TaskEntityFetcher
{
    private const ALIAS = 't';

    private const ALIAS_TASK_USER = 'tu';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DoctrinePaginationApplier $paginationApplier,
    ) {
    }

    public function findById(string $id): ?TaskEntity
    {
        /** @var TaskEntity|null $entity */
        $entity = $this->baseQuery()
            ->where(self::ALIAS.'.'.TaskEntity::FIELD_ID.' = :id')
            ->setParameter('id', Uuid::fromString($id), 'uuid')
            ->getQuery()
            ->getOneOrNullResult();

        return $entity;
    }

    public function findViewById(string $id): ?TaskEntity
    {
        /** @var list<TaskEntity> $entities */
        $entities = $this->viewQuery()
            ->where(self::ALIAS.'.'.TaskEntity::FIELD_ID.' = :id')
            ->setParameter('id', Uuid::fromString($id), 'uuid')
            ->getQuery()
            ->getResult();

        return $this->uniqueById($entities)[0] ?? null;
    }

    /**
     * @param list<string> $ids
     *
     * @return list<TaskEntity>
     */
    public function listByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $ids = array_values(array_unique($ids));
        $qb = $this->baseQuery();
        $orX = $qb->expr()->orX();

        foreach ($ids as $index => $id) {
            $param = 'id'.$index;
            $orX->add(self::ALIAS.'.'.TaskEntity::FIELD_ID.' = :'.$param);
            $qb->setParameter($param, Uuid::fromString($id), 'uuid');
        }

        /** @var list<TaskEntity> $entities */
        $entities = $qb
            ->where($orX)
            ->getQuery()
            ->getResult();

        return $entities;
    }

    /** @return list<TaskEntity> */
    public function listByUserId(string $userId): array
    {
        /** @var list<TaskEntity> $entities */
        $entities = $this->baseQuery()
            ->andWhere($this->visibleToUserExistsDql())
            ->setParameter('userId', Uuid::fromString($userId), 'uuid')
            ->orderBy(self::ALIAS.'.'.TaskEntity::FIELD_CREATED_AT, 'DESC')
            ->addOrderBy(self::ALIAS.'.'.TaskEntity::FIELD_ID, 'DESC')
            ->getQuery()
            ->getResult();

        return $entities;
    }

    public function countVisibleToUser(string $userId): int
    {
        $count = $this->entityManager->createQueryBuilder()
            ->select('COUNT('.self::ALIAS.'.'.TaskEntity::FIELD_ID.')')
            ->from(TaskEntity::class, self::ALIAS)
            ->andWhere($this->visibleToUserExistsDql())
            ->setParameter('userId', Uuid::fromString($userId), 'uuid')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count;
    }

    /** @return list<TaskEntity> */
    public function listByOwnerId(string $ownerId): array
    {
        /** @var list<TaskEntity> $entities */
        $entities = $this->baseQuery()
            ->andWhere(self::ALIAS.'.'.TaskEntity::FIELD_OWNER_ID.' = :ownerId')
            ->setParameter('ownerId', Uuid::fromString($ownerId), 'uuid')
            ->getQuery()
            ->getResult();

        return $entities;
    }

    /**
     * Page of view entities. Limit applies to tasks, not joined task_users rows.
     *
     * @return list<TaskEntity>
     */
    public function listViewPageByUserId(string $userId, PaginationModel $pagination): array
    {
        $qb = $this->baseQuery()
            ->andWhere($this->visibleToUserExistsDql())
            ->setParameter('userId', Uuid::fromString($userId), 'uuid')
            ->orderBy(self::ALIAS.'.'.TaskEntity::FIELD_CREATED_AT, 'DESC')
            ->addOrderBy(self::ALIAS.'.'.TaskEntity::FIELD_ID, 'DESC');

        $this->paginationApplier->applyLimit($qb, $pagination);

        /** @var list<TaskEntity> $page */
        $page = $qb->getQuery()->getResult();

        if ($page === []) {
            return [];
        }

        $ids = array_map(
            static fn (TaskEntity $entity): string => UidUtils::toString($entity->getId()),
            $page,
        );

        return $this->listViewsByIdsInOrder($ids);
    }

    /**
     * @param list<string> $ids
     *
     * @return list<TaskEntity>
     */
    private function listViewsByIdsInOrder(array $ids): array
    {
        $qb = $this->viewQuery();
        $orX = $qb->expr()->orX();

        foreach ($ids as $index => $id) {
            $param = 'viewId'.$index;
            $orX->add(self::ALIAS.'.'.TaskEntity::FIELD_ID.' = :'.$param);
            $qb->setParameter($param, Uuid::fromString($id), 'uuid');
        }

        /** @var list<TaskEntity> $entities */
        $entities = $qb
            ->where($orX)
            ->getQuery()
            ->getResult();

        $byId = [];

        foreach ($this->uniqueById($entities) as $entity) {
            $byId[UidUtils::toString($entity->getId())] = $entity;
        }

        $ordered = [];

        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    private function visibleToUserExistsDql(): string
    {
        return 'EXISTS (
                    SELECT 1 FROM '.TaskUserEntity::class.' ftu
                    WHERE ftu.'.TaskUserEntity::FIELD_TASK.' = '.self::ALIAS.'
                      AND ftu.'.TaskUserEntity::FIELD_USER_ID.' = :userId
                ) OR NOT EXISTS (
                    SELECT 1 FROM '.TaskUserEntity::class.' ftu2
                    WHERE ftu2.'.TaskUserEntity::FIELD_TASK.' = '.self::ALIAS.'
                )';
    }

    private function baseQuery(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select(self::ALIAS)
            ->from(TaskEntity::class, self::ALIAS);
    }

    private function viewQuery(): QueryBuilder
    {
        return $this->baseQuery()
            ->addSelect(self::ALIAS_TASK_USER)
            ->leftJoin(self::ALIAS.'.'.TaskEntity::FIELD_TASK_USERS, self::ALIAS_TASK_USER);
    }

    /**
     * @param list<TaskEntity> $entities
     *
     * @return list<TaskEntity>
     */
    private function uniqueById(array $entities): array
    {
        $unique = [];

        foreach ($entities as $entity) {
            $unique[UidUtils::toString($entity->getId())] = $entity;
        }

        return array_values($unique);
    }
}
