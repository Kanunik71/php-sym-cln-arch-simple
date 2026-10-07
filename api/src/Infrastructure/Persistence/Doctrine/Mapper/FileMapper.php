<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Application\Model\File\FileModel;
use App\Infrastructure\Persistence\Doctrine\Entity\FileEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Infrastructure\Persistence\Doctrine\EntityReferenceAdapter;
use App\Shared\Utils\UidUtils;
use Symfony\Component\Uid\Uuid;

final readonly class FileMapper
{
    public function __construct(
        private EntityReferenceAdapter $entityReference,
    ) {
    }

    public function toModel(FileEntity $entity): FileModel
    {
        return new FileModel(
            id: UidUtils::toString($entity->getId()),
            ownerId: UidUtils::toString($entity->getOwnerId()),
            originalName: $entity->getOriginalName(),
            mimeType: $entity->getMimeType(),
            sizeBytes: $entity->getSizeBytes(),
            storageKey: $entity->getStorageKey(),
            status: $entity->getStatus(),
            createdAt: $entity->getCreatedAt(),
            updatedAt: $entity->getUpdatedAt(),
        );
    }

    /**
     * @param list<FileEntity> $entities
     *
     * @return list<FileModel>
     */
    public function toModelArray(array $entities): array
    {
        return array_map(
            fn (FileEntity $entity): FileModel => $this->toModel($entity),
            $entities,
        );
    }

    public function toEntity(FileModel $file, ?FileEntity $entity = null): FileEntity
    {
        $entity ??= new FileEntity();
        $entity->setId(Uuid::fromString($file->id));

        $entity
            ->setOwner($this->entityReference->getReference(UserEntity::class, Uuid::fromString($file->ownerId)))
            ->setOriginalName($file->originalName)
            ->setMimeType($file->mimeType)
            ->setSizeBytes($file->sizeBytes)
            ->setStorageKey($file->storageKey)
            ->setStatus($file->status)
            ->setCreatedAt($file->createdAt)
            ->setUpdatedAt($file->updatedAt);

        return $entity;
    }
}
