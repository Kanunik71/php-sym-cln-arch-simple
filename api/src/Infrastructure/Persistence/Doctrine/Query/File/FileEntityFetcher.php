<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query\File;

use App\Application\Enum\File\FileStatusEnum;
use App\Infrastructure\Persistence\Doctrine\Entity\FileEntity;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;

/**
 * Loads FileEntity root only — ownerId is a scalar column (no owner join).
 */
final readonly class FileEntityFetcher
{
    private const ALIAS = 'f';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(string $id): ?FileEntity
    {
        /** @var FileEntity|null $entity */
        $entity = $this->baseQuery()
            ->where(self::ALIAS.'.'.FileEntity::FIELD_ID.' = :id')
            ->setParameter('id', Uuid::fromString($id), 'uuid')
            ->getQuery()
            ->getOneOrNullResult();

        return $entity;
    }

    /**
     * @param list<string> $ids
     *
     * @return list<FileEntity>
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
            $orX->add(self::ALIAS.'.'.FileEntity::FIELD_ID.' = :'.$param);
            $qb->setParameter($param, Uuid::fromString($id), 'uuid');
        }

        /** @var list<FileEntity> $entities */
        $entities = $qb
            ->where($orX)
            ->getQuery()
            ->getResult();

        return $entities;
    }

    /** @return list<FileEntity> */
    public function listExpiredTemporary(DateTimeImmutable $updatedBefore): array
    {
        /** @var list<FileEntity> $entities */
        $entities = $this->baseQuery()
            ->where(self::ALIAS.'.'.FileEntity::FIELD_STATUS.' = :status')
            ->andWhere(self::ALIAS.'.'.FileEntity::FIELD_UPDATED_AT.' < :updatedBefore')
            ->setParameter('status', FileStatusEnum::Temporary)
            ->setParameter('updatedBefore', $updatedBefore)
            ->getQuery()
            ->getResult();

        return $entities;
    }

    /** @return list<FileEntity> */
    public function listByOwnerId(string $ownerId): array
    {
        /** @var list<FileEntity> $entities */
        $entities = $this->baseQuery()
            ->where(self::ALIAS.'.'.FileEntity::FIELD_OWNER_ID.' = :ownerId')
            ->setParameter('ownerId', Uuid::fromString($ownerId), 'uuid')
            ->getQuery()
            ->getResult();

        return $entities;
    }

    private function baseQuery(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select(self::ALIAS)
            ->from(FileEntity::class, self::ALIAS);
    }
}
