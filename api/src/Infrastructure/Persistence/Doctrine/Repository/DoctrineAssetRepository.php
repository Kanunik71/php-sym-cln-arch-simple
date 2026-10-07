<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Application\Model\Asset\Read\AssetDetailModel;
use App\Application\Model\Asset\AssetModel;
use App\Application\Model\Asset\Read\AssetViewModel;
use App\Application\Port\Asset\AssetRepositoryInterface;
use App\Application\Port\File\FileDownloadUrlProviderInterface;
use App\Application\Exception\Asset\AssetNotFoundException;
use App\Infrastructure\Persistence\Doctrine\Entity\AssetEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\AssetMapper;
use App\Infrastructure\Persistence\Doctrine\Query\Asset\AssetEntityFetcher;
use App\Infrastructure\Persistence\Doctrine\Utils\DoctrineUuidUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineAssetRepository implements AssetRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AssetMapper $assetMapper,
        private AssetEntityFetcher $assetEntityFetcher,
        private FileDownloadUrlProviderInterface $fileDownloadUrlProvider,
    ) {
    }

    public function save(
        AssetModel $asset,
        ?array $taskIds = null,
        ?array $imageFileIds = null,
    ): AssetModel {
        $entity = $this->assetEntityFetcher->findViewById($asset->id);

        $entity = $this->assetMapper->toEntity($asset, $entity, $taskIds, $imageFileIds);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $this->findOrFail($asset->id);
    }

    public function findById(string $id): ?AssetModel
    {
        $entity = $this->assetEntityFetcher->findById($id);

        return $entity !== null ? $this->assetMapper->toModel($entity) : null;
    }

    public function findOrFail(string $id): AssetModel
    {
        return $this->findById($id) ?? throw AssetNotFoundException::withId($id);
    }

    public function findViewOrFail(string $id): AssetViewModel
    {
        $entity = $this->assetEntityFetcher->findViewById($id);

        if ($entity === null) {
            throw AssetNotFoundException::withId($id);
        }

        return $this->assetMapper->toViewModel($entity, $this->fileDownloadUrlProvider);
    }

    public function findDetailOrFail(string $id): AssetDetailModel
    {
        $entity = $this->assetEntityFetcher->findDetailById($id);

        if ($entity === null) {
            throw AssetNotFoundException::withId($id);
        }

        return $this->assetMapper->toDetailModel($entity, $this->fileDownloadUrlProvider);
    }

    public function listViewsByOwnerId(string $ownerId): array
    {
        return $this->assetMapper->toViewModelArray(
            $this->assetEntityFetcher->listViewByOwnerId($ownerId),
            $this->fileDownloadUrlProvider,
        );
    }

    public function listViewsByTaskId(string $taskId): array
    {
        return $this->assetMapper->toViewModelArray(
            $this->assetEntityFetcher->listViewByTaskId($taskId),
            $this->fileDownloadUrlProvider,
        );
    }

    public function listByOwnerId(string $ownerId): array
    {
        return $this->assetMapper->toModelArray($this->assetEntityFetcher->listByOwnerId($ownerId));
    }

    public function listTaskIds(string $assetId): array
    {
        $entity = $this->assetEntityFetcher->findViewById($assetId);

        if ($entity === null) {
            throw AssetNotFoundException::withId($assetId);
        }

        return $this->assetMapper->extractTaskIds($entity);
    }

    public function listImageFileIds(string $assetId): array
    {
        $entity = $this->assetEntityFetcher->findViewById($assetId);

        if ($entity === null) {
            throw AssetNotFoundException::withId($assetId);
        }

        return $this->assetMapper->extractImageFileIds($entity);
    }

    public function belongsToTask(string $assetId, string $taskId): bool
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $joinTable = AssetEntity::JOIN_TABLE_TASK;
        $assetCol = AssetEntity::JOIN_COLUMN_ASSET_ID;
        $taskCol = AssetEntity::JOIN_COLUMN_TASK_ID;

        $sql = <<<SQL
            SELECT 1 FROM {$joinTable} WHERE {$assetCol} = :{$assetCol} AND {$taskCol} = :{$taskCol} LIMIT 1
            SQL;

        $exists = $connection->fetchOne(
            $sql,
            [
                $assetCol => DoctrineUuidUtils::toDatabaseValue($assetId, $platform),
                $taskCol => DoctrineUuidUtils::toDatabaseValue($taskId, $platform),
            ],
            [
                $assetCol => DoctrineUuidUtils::bindingType(),
                $taskCol => DoctrineUuidUtils::bindingType(),
            ],
        );

        return $exists !== false && $exists !== null;
    }

    public function attachTask(string $assetId, string $taskId): void
    {
        if ($this->belongsToTask($assetId, $taskId)) {
            return;
        }

        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $joinTable = AssetEntity::JOIN_TABLE_TASK;
        $assetCol = AssetEntity::JOIN_COLUMN_ASSET_ID;
        $taskCol = AssetEntity::JOIN_COLUMN_TASK_ID;

        $sql = <<<SQL
            INSERT IGNORE INTO {$joinTable} ({$assetCol}, {$taskCol}) VALUES (:{$assetCol}, :{$taskCol})
            SQL;

        $connection->executeStatement(
            $sql,
            [
                $assetCol => DoctrineUuidUtils::toDatabaseValue($assetId, $platform),
                $taskCol => DoctrineUuidUtils::toDatabaseValue($taskId, $platform),
            ],
            [
                $assetCol => DoctrineUuidUtils::bindingType(),
                $taskCol => DoctrineUuidUtils::bindingType(),
            ],
        );

        $this->detachManagedAsset($assetId);
    }

    public function detachTask(string $assetId, string $taskId): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $joinTable = AssetEntity::JOIN_TABLE_TASK;
        $assetCol = AssetEntity::JOIN_COLUMN_ASSET_ID;
        $taskCol = AssetEntity::JOIN_COLUMN_TASK_ID;

        $sql = <<<SQL
            DELETE FROM {$joinTable} WHERE {$assetCol} = :{$assetCol} AND {$taskCol} = :{$taskCol}
            SQL;

        $connection->executeStatement(
            $sql,
            [
                $assetCol => DoctrineUuidUtils::toDatabaseValue($assetId, $platform),
                $taskCol => DoctrineUuidUtils::toDatabaseValue($taskId, $platform),
            ],
            [
                $assetCol => DoctrineUuidUtils::bindingType(),
                $taskCol => DoctrineUuidUtils::bindingType(),
            ],
        );

        $this->detachManagedAsset($assetId);
    }

    public function delete(AssetModel $asset): void
    {
        $entity = $this->assetEntityFetcher->findViewById($asset->id);

        if ($entity === null) {
            return;
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }

    private function detachManagedAsset(string $assetId): void
    {
        $entity = $this->entityManager->find(AssetEntity::class, Uuid::fromString($assetId));

        if ($entity !== null) {
            $this->entityManager->detach($entity);
        }
    }
}
