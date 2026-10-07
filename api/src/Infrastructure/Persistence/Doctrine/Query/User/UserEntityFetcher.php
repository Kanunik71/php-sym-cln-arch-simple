<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query\User;

use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Loads UserEntity root only — avatarFileId is a scalar column (no avatar join).
 * Inverse taskUsers is not part of Domain User — not loaded here.
 */
final readonly class UserEntityFetcher
{
    private const ALIAS = 'u';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(string $id): ?UserEntity
    {
        /** @var UserEntity|null $entity */
        $entity = $this->baseQuery()
            ->where(self::ALIAS.'.'.UserEntity::FIELD_ID.' = :id')
            ->setParameter('id', Uuid::fromString($id), 'uuid')
            ->getQuery()
            ->getOneOrNullResult();

        return $entity;
    }

    public function findByEmail(string $email): ?UserEntity
    {
        /** @var UserEntity|null $entity */
        $entity = $this->baseQuery()
            ->where(self::ALIAS.'.'.UserEntity::FIELD_EMAIL.' = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();

        return $entity;
    }

    /** @return list<UserEntity> */
    public function listAll(): array
    {
        /** @var list<UserEntity> $entities */
        $entities = $this->baseQuery()
            ->orderBy(self::ALIAS.'.'.UserEntity::FIELD_CREATED_AT, 'DESC')
            ->getQuery()
            ->getResult();

        return $entities;
    }

    /**
     * @return list<UserEntity>
     */
    public function listBatch(int $offset, int $limit): array
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Offset must be >= 0.');
        }

        if ($limit < 1) {
            throw new InvalidArgumentException('Limit must be at least 1.');
        }

        /** @var list<UserEntity> $entities */
        $entities = $this->baseQuery()
            ->orderBy(self::ALIAS.'.'.UserEntity::FIELD_ID, 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $entities;
    }

    /**
     * @param list<string> $ids
     *
     * @return list<UserEntity>
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
            $orX->add(self::ALIAS.'.'.UserEntity::FIELD_ID.' = :'.$param);
            $qb->setParameter($param, Uuid::fromString($id), 'uuid');
        }

        /** @var list<UserEntity> $entities */
        $entities = $qb
            ->where($orX)
            ->getQuery()
            ->getResult();

        return $entities;
    }

    /**
     * QueryBuilder for criteria / pagination callers.
     * Root alias is always {@see self::ALIAS}.
     */
    public function createListQueryBuilder(): QueryBuilder
    {
        return $this->baseQuery();
    }

    public function rootAlias(): string
    {
        return self::ALIAS;
    }

    private function baseQuery(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select(self::ALIAS)
            ->from(UserEntity::class, self::ALIAS);
    }
}
