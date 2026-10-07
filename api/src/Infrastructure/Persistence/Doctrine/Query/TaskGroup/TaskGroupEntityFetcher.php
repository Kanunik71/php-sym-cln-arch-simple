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

    /** @return list<TaskGroupEntity> */
    public function listAll(): array
    {
        /** @var list<TaskGroupEntity> $entities */
        $entities = $this->baseQuery()
            ->orderBy(self::ALIAS.'.'.TaskGroupEntity::FIELD_CREATED_AT, 'DESC')
            ->getQuery()
            ->getResult();

        return $entities;
    }

    /**
     * @return list<TaskGroupEntity>
     */
    public function listBatch(int $offset, int $limit): array
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset must be >= 0.');
        }

        if ($limit < 1) {
            throw new InvalidArgumentException('Limit must be at least 1.');
        }

        /** @var list<TaskGroupEntity> $entities */
        $entities = $this->baseQuery()
            ->orderBy(self::ALIAS.'.'.TaskGroupEntity::FIELD_ID, 'ASC')
            ->setFirstResult($offset)
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
