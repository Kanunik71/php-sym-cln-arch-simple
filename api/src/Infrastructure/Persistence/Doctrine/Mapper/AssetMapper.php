<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Application\Mapper\Common\FileItemModelMapper;
use App\Application\Model\Asset\Read\AssetDetailModel;
use App\Application\Model\Asset\AssetModel;
use App\Application\Model\Asset\Read\AssetViewModel;
use App\Application\Port\File\FileDownloadUrlProviderInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\AssetEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\AssetFileEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\FileEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Infrastructure\Persistence\Doctrine\EntityReferenceAdapter;
use App\Infrastructure\Persistence\Doctrine\Utils\DoctrineCollectionAssertUtils;
use App\Shared\Utils\DateUtils;
use App\Shared\Utils\MoneyUtils;
use App\Shared\Utils\UidUtils;
use Symfony\Component\Uid\Uuid;

final readonly class AssetMapper
{
    public function __construct(
        private EntityReferenceAdapter $entityReference,
        private TaskMapper $taskMapper,
    ) {
    }

    public function toModel(AssetEntity $entity): AssetModel
    {
        return new AssetModel(
            id: UidUtils::toString($entity->getId()),
            name: $entity->getName(),
            price: MoneyUtils::fromString($entity->getPrice()),
            type: $entity->getType(),
            ownerId: UidUtils::toString($entity->getOwnerId()),
            createdAt: $entity->getCreatedAt(),
            updatedAt: $entity->getUpdatedAt(),
        );
    }

    /**
     * @param list<AssetEntity> $entities
     *
     * @return list<AssetModel>
     */
    public function toModelArray(array $entities): array
    {
        return array_map(
            fn (AssetEntity $entity): AssetModel => $this->toModel($entity),
            $entities,
        );
    }

    public function toViewModel(
        AssetEntity $entity,
        FileDownloadUrlProviderInterface $urlProvider,
    ): AssetViewModel {
        return new AssetViewModel(
            id: UidUtils::toString($entity->getId()),
            ownerId: UidUtils::toString($entity->getOwnerId()),
            name: $entity->getName(),
            taskIds: $this->extractTaskIds($entity),
            price: MoneyUtils::fromString($entity->getPrice()),
            type: $entity->getType(),
            createdAt: DateUtils::toAtom($entity->getCreatedAt()),
            inProgress: $this->isInProgress($entity->getTasks()),
            mainImage: FileItemModelMapper::firstFromFileIds(
                $this->extractImageFileIds($entity),
                $urlProvider,
            ),
        );
    }

    /**
     * @param list<AssetEntity> $entities
     *
     * @return list<AssetViewModel>
     */
    public function toViewModelArray(
        array $entities,
        FileDownloadUrlProviderInterface $urlProvider,
    ): array {
        return array_map(
            fn (AssetEntity $entity): AssetViewModel => $this->toViewModel($entity, $urlProvider),
            $entities,
        );
    }

    public function toDetailModel(
        AssetEntity $entity,
        FileDownloadUrlProviderInterface $urlProvider,
    ): AssetDetailModel {
        DoctrineCollectionAssertUtils::assertInitialized(
            $entity->getTasks(),
            AssetEntity::class.'::'.AssetEntity::FIELD_TASKS,
        );
        DoctrineCollectionAssertUtils::assertInitialized(
            $entity->getAssetFiles(),
            AssetEntity::class.'::'.AssetEntity::FIELD_ASSET_FILES,
        );

        $tasks = [];
        $imageFileIds = [];

        foreach ($entity->getTasks() as $task) {
            $tasks[] = $this->taskMapper->toLightModel($task);
        }

        foreach ($entity->getAssetFiles() as $assetFile) {
            $imageFileIds[] = UidUtils::toString($assetFile->getFileId());
        }

        return new AssetDetailModel(
            id: UidUtils::toString($entity->getId()),
            ownerId: UidUtils::toString($entity->getOwnerId()),
            name: $entity->getName(),
            tasks: $tasks,
            price: MoneyUtils::fromString($entity->getPrice()),
            type: $entity->getType(),
            createdAt: DateUtils::toAtom($entity->getCreatedAt()),
            inProgress: $this->isInProgress($entity->getTasks()),
            images: FileItemModelMapper::fromFileIds($imageFileIds, $urlProvider),
        );
    }

    /**
     * @param list<string>|null $taskIds
     * @param list<string>|null $imageFileIds
     */
    public function toEntity(
        AssetModel $asset,
        ?AssetEntity $entity = null,
        ?array $taskIds = null,
        ?array $imageFileIds = null,
    ): AssetEntity {
        $entity ??= new AssetEntity();
        $entity->setId(Uuid::fromString($asset->id));

        if ($taskIds !== null) {
            $entity->getTasks()->clear();

            foreach ($taskIds as $taskId) {
                $entity->getTasks()->add(
                    $this->entityReference->getReference(TaskEntity::class, Uuid::fromString($taskId)),
                );
            }
        }

        if ($imageFileIds !== null) {
            $entity->getAssetFiles()->clear();

            foreach ($imageFileIds as $position => $fileId) {
                $assetFile = new AssetFileEntity();
                $assetFile
                    ->setAsset($entity)
                    ->setFile($this->entityReference->getReference(FileEntity::class, Uuid::fromString($fileId)))
                    ->setPosition($position);
                $entity->getAssetFiles()->add($assetFile);
            }
        }

        $entity
            ->setOwner($this->entityReference->getReference(UserEntity::class, Uuid::fromString($asset->ownerId)))
            ->setName($asset->name)
            ->setPrice(MoneyUtils::toString($asset->price))
            ->setType($asset->type)
            ->setCreatedAt($asset->createdAt)
            ->setUpdatedAt($asset->updatedAt);

        return $entity;
    }

    /** @return list<string> */
    public function extractTaskIds(AssetEntity $entity): array
    {
        DoctrineCollectionAssertUtils::assertInitialized(
            $entity->getTasks(),
            AssetEntity::class.'::'.AssetEntity::FIELD_TASKS,
        );

        $taskIds = [];
        foreach ($entity->getTasks() as $task) {
            $taskIds[] = UidUtils::toString($task->getId());
        }

        return $taskIds;
    }

    /**
     * @return list<string>
     */
    public function extractImageFileIds(AssetEntity $entity): array
    {
        DoctrineCollectionAssertUtils::assertInitialized(
            $entity->getAssetFiles(),
            AssetEntity::class.'::'.AssetEntity::FIELD_ASSET_FILES,
        );

        $imageFileIds = [];
        foreach ($entity->getAssetFiles() as $assetFile) {
            $imageFileIds[] = UidUtils::toString($assetFile->getFileId());
        }

        return $imageFileIds;
    }

    /** @param iterable<TaskEntity> $tasks */
    private function isInProgress(iterable $tasks): bool
    {
        foreach ($tasks as $task) {
            if ($task->getStatus()->keepsAssetInProgress()) {
                return true;
            }
        }

        return false;
    }
}
