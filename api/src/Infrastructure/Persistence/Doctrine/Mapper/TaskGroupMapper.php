<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Infrastructure\Persistence\Doctrine\Entity\TaskGroupEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Infrastructure\Persistence\Doctrine\EntityReferenceAdapter;
use App\Shared\Utils\UidUtils;
use Symfony\Component\Uid\Uuid;

final readonly class TaskGroupMapper
{
    public function __construct(
        private EntityReferenceAdapter $entityReference,
    ) {
    }

    public function toModel(TaskGroupEntity $entity): TaskGroupModel
    {
        return new TaskGroupModel(
            id: UidUtils::toString($entity->getId()),
            ownerId: UidUtils::toString($entity->getOwnerId()),
            name: $entity->getName(),
            status: $entity->getStatus(),
            createdAt: $entity->getCreatedAt(),
        );
    }

    /**
     * @param list<TaskGroupEntity> $entities
     *
     * @return list<TaskGroupModel>
     */
    public function toModelArray(array $entities): array
    {
        return array_map(
            fn (TaskGroupEntity $entity): TaskGroupModel => $this->toModel($entity),
            $entities,
        );
    }

    public function toEntity(TaskGroupModel $taskGroup, ?TaskGroupEntity $entity = null): TaskGroupEntity
    {
        $entity ??= new TaskGroupEntity();
        $entity->setId(Uuid::fromString($taskGroup->id));

        $entity
            ->setOwner($this->entityReference->getReference(UserEntity::class, Uuid::fromString($taskGroup->ownerId)))
            ->setName($taskGroup->name)
            ->setStatus($taskGroup->status)
            ->setCreatedAt($taskGroup->createdAt);

        return $entity;
    }
}
