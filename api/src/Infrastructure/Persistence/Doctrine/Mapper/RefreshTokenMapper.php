<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Application\Model\Auth\RefreshTokenModel;
use App\Infrastructure\Persistence\Doctrine\Entity\RefreshTokenEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Infrastructure\Persistence\Doctrine\EntityReferenceAdapter;
use App\Shared\Utils\UidUtils;
use Symfony\Component\Uid\Uuid;

final class RefreshTokenMapper
{
    public function __construct(
        private readonly EntityReferenceAdapter $entityReference,
    ) {
    }

    public function toModel(RefreshTokenEntity $entity): RefreshTokenModel
    {
        return new RefreshTokenModel(
            id: UidUtils::toString($entity->getId()),
            userId: UidUtils::toString($entity->getUserId()),
            tokenHash: $entity->getTokenHash(),
            familyId: UidUtils::toString($entity->getFamilyId()),
            createdAt: $entity->getCreatedAt(),
            expiresAt: $entity->getExpiresAt(),
            revokedAt: $entity->getRevokedAt(),
        );
    }

    public function toEntity(
        RefreshTokenModel $token,
        ?RefreshTokenEntity $entity = null,
    ): RefreshTokenEntity {
        $entity ??= new RefreshTokenEntity();
        $entity->setId(Uuid::fromString($token->id));

        /** @var UserEntity $user */
        $user = $this->entityReference->getReference(
            UserEntity::class,
            Uuid::fromString($token->userId),
        );

        $entity
            ->setUser($user)
            ->setTokenHash($token->tokenHash)
            ->setFamilyId(Uuid::fromString($token->familyId))
            ->setCreatedAt($token->createdAt)
            ->setExpiresAt($token->expiresAt)
            ->setRevokedAt($token->revokedAt);

        return $entity;
    }
}
