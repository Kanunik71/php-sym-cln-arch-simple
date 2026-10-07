<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Application\Model\User\UserModel;
use App\Infrastructure\Persistence\Doctrine\Entity\FileEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Infrastructure\Persistence\Doctrine\EntityReferenceAdapter;
use App\Shared\Utils\UidUtils;
use Symfony\Component\Uid\Uuid;

final class UserMapper
{
    public function __construct(
        private readonly EntityReferenceAdapter $entityReference,
    ) {
    }

    public function toModel(UserEntity $entity): UserModel
    {
        $avatarFileId = $entity->getAvatarFileId();

        return new UserModel(
            id: UidUtils::toString($entity->getId()),
            fname: $entity->getFname(),
            lname: $entity->getLname(),
            email: $entity->getEmail(),
            passwordHash: $entity->getPassword(),
            createdAt: $entity->getCreatedAt(),
            updatedAt: $entity->getUpdatedAt(),
            city: $entity->getCity(),
            avatarFileId: $avatarFileId !== null ? UidUtils::toString($avatarFileId) : null,
            phone: $entity->getPhone(),
        );
    }

    /**
     * @param list<UserEntity> $entities
     *
     * @return list<UserModel>
     */
    public function toModelArray(array $entities): array
    {
        return array_map(
            fn (UserEntity $entity): UserModel => $this->toModel($entity),
            $entities,
        );
    }

    public function toEntity(UserModel $user, ?UserEntity $entity = null): UserEntity
    {
        $entity ??= new UserEntity();
        $entity->setId(Uuid::fromString($user->id));

        $avatarFile = $user->avatarFileId !== null
            ? $this->entityReference->getReference(FileEntity::class, Uuid::fromString($user->avatarFileId))
            : null;

        $entity
            ->setFname($user->fname)
            ->setLname($user->lname)
            ->setEmail($user->email)
            ->setPassword($user->passwordHash)
            ->setCreatedAt($user->createdAt)
            ->setUpdatedAt($user->updatedAt)
            ->setCity($user->city)
            ->setPhone($user->phone)
            ->setAvatarFile($avatarFile);

        return $entity;
    }
}
