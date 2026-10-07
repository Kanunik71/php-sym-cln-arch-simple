<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query\Asset;

use App\Infrastructure\Persistence\Doctrine\Entity\AssetEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;
use App\Shared\Utils\UidUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;

/**
 * Loads AssetEntity with the associations the caller needs (no lazy follow-up for Mapper).
 *
 * - Light (`findById` / `listByOwnerId`): root only — ownerId is a scalar column.
 * - View (`findViewById` / `listViewBy*`): tasks + assetFiles.
 * - Detail (`findDetailById`): same as view (task.parentId is scalar on Task).
 */
final readonly class AssetEntityFetcher
{
    private const ALIAS = 'a';

    private const ALIAS_TASK = 't';

    private const ALIAS_ASSET_FILE = 'af';

    private const ALIAS_FILTER_TASK = 'ft';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(string $id): ?AssetEntity
    {
        /** @var AssetEntity|null $entity */
        $entity = $this->baseQuery()
            ->where(self::ALIAS.'.'.AssetEntity::FIELD_ID.' = :id')
            ->setParameter('id', Uuid::fromString($id), 'uuid')
            ->getQuery()
            ->getOneOrNullResult();

        return $entity;
    }

    public function findViewById(string $id): ?AssetEntity
    {
        /** @var list<AssetEntity> $entities */
        $entities = $this->viewQuery()
            ->where(self::ALIAS.'.'.AssetEntity::FIELD_ID.' = :id')
            ->setParameter('id', Uuid::fromString($id), 'uuid')
            ->getQuery()
            ->getResult();

        $unique = $this->uniqueById($entities);

        return $unique[0] ?? null;
    }

    public function findDetailById(string $id): ?AssetEntity
    {
        return $this->findViewById($id);
    }

    /** @return list<AssetEntity> */
    public function listByOwnerId(string $ownerId): array
    {
        /** @var list<AssetEntity> $entities */
        $entities = $this->baseQuery()
            ->where(self::ALIAS.'.'.AssetEntity::FIELD_OWNER_ID.' = :ownerId')
            ->setParameter('ownerId', Uuid::fromString($ownerId), 'uuid')
            ->orderBy(self::ALIAS.'.'.AssetEntity::FIELD_CREATED_AT, 'DESC')
            ->addOrderBy(self::ALIAS.'.'.AssetEntity::FIELD_ID, 'DESC')
            ->getQuery()
            ->getResult();

        return $entities;
    }

    /** @return list<AssetEntity> */
    public function listViewByOwnerId(string $ownerId): array
    {
        /** @var list<AssetEntity> $entities */
        $entities = $this->viewQuery()
            ->where(self::ALIAS.'.'.AssetEntity::FIELD_OWNER_ID.' = :ownerId')
            ->setParameter('ownerId', Uuid::fromString($ownerId), 'uuid')
            ->orderBy(self::ALIAS.'.'.AssetEntity::FIELD_CREATED_AT, 'DESC')
            ->addOrderBy(self::ALIAS.'.'.AssetEntity::FIELD_ID, 'DESC')
            ->getQuery()
            ->getResult();

        return $this->uniqueById($entities);
    }

    /** @return list<AssetEntity> */
    public function listViewByTaskId(string $taskId): array
    {
        /** @var list<AssetEntity> $entities */
        $entities = $this->viewQuery()
            ->innerJoin(
                self::ALIAS.'.'.AssetEntity::FIELD_TASKS,
                self::ALIAS_FILTER_TASK,
                'WITH',
                self::ALIAS_FILTER_TASK.'.'.TaskEntity::FIELD_ID.' = :taskId',
            )
            ->setParameter('taskId', Uuid::fromString($taskId), 'uuid')
            ->orderBy(self::ALIAS.'.'.AssetEntity::FIELD_CREATED_AT, 'DESC')
            ->addOrderBy(self::ALIAS.'.'.AssetEntity::FIELD_ID, 'DESC')
            ->getQuery()
            ->getResult();

        return $this->uniqueById($entities);
    }

    private function baseQuery(): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select(self::ALIAS)
            ->from(AssetEntity::class, self::ALIAS);
    }

    private function viewQuery(): QueryBuilder
    {
        return $this->baseQuery()
            ->addSelect(self::ALIAS_TASK, self::ALIAS_ASSET_FILE)
            ->leftJoin(self::ALIAS.'.'.AssetEntity::FIELD_TASKS, self::ALIAS_TASK)
            ->leftJoin(self::ALIAS.'.'.AssetEntity::FIELD_ASSET_FILES, self::ALIAS_ASSET_FILE);
    }

    /**
     * Fetch-joining two collections can duplicate root rows; collapse by identity.
     *
     * @param list<AssetEntity> $entities
     *
     * @return list<AssetEntity>
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
