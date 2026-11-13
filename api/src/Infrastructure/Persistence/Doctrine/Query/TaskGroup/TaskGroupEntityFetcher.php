<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query\TaskGroup;

use App\Infrastructure\Persistence\Doctrine\Entity\TaskGroupEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Loads TaskGroupEntity root only — ownerId is a scalar column (no owner join).
 */
final readonly class TaskGroupEntityFetcher
{
    private const ALIAS = 'tg';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(string $id): ?TaskGroupEntity
    {
        /** @var TaskGroupEntity|null $entity */
        $entity = $this->baseQuery()
            ->where(self::ALIAS.'.'.TaskGroupEntity::FIELD_ID.' = :id')
            ->setParameter('id', Uuid::fromString($id), 'uuid')
            ->getQuery()
            ->getOneOrNullResult();

        return $entity;
    }

    /**
     * @param list<string> $ids
     *
     * @return list<TaskGroupEntity>
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
            $orX->add(self::ALIAS.'.'.TaskGroupEntity::FIELD_ID.' = :'.$param);
            $qb->setParameter($param, Uuid::fromString($id), 'uuid');
        }

        /** @var list<TaskGroupEntity> $entities */
        $entities = $qb
            ->where($orX)
            ->getQuery()
            ->getResult();

        return $entities;
    }

    /**
     * Reads a batch before the given cursor, ordered by id descending.
     *
     * Keyset (seek) pagination: the cursor is the id of the first row of the
     * previous batch. Unlike OFFSET it does not re-scan and discard the
     * skipped rows, so a full traversal stays linear in the number of rows.
     *
     * Ids are UUID v7 and therefore grow with creation time, so descending
     * order by id is descending order by creation time.
     *
     * @return list<TaskGroupEntity>
     */
    public function listBatchBefore(string $beforeId, int $limit): array
    {
        if ($limit < 1) {
            throw new InvalidArgumentException('Limit must be at least 1.');
        }

        /** @var list<TaskGroupEntity> $entities */
        $entities = $this->baseQuery()
            ->where(self::ALIAS.'.'.TaskGroupEntity::FIELD_ID.' < :beforeId')
            ->setParameter('beforeId', Uuid::fromString($beforeId), 'uuid')
            ->orderBy(self::ALIAS.'.'.TaskGroupEntity::FIELD_ID, 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $entities;
    }

    private function baseQuery(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select(self::ALIAS)
            ->from(TaskGroupEntity::class, self::ALIAS);
    }
}
