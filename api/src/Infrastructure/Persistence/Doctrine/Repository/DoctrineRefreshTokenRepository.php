<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Application\Model\Auth\RefreshTokenModel;
use App\Application\Port\Auth\RefreshTokenRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\RefreshTokenEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\RefreshTokenMapper;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RefreshTokenMapper $mapper,
    ) {
    }

    public function save(RefreshTokenModel $token): RefreshTokenModel
    {
        $entity = $this->entityManager->find(
            RefreshTokenEntity::class,
            Uuid::fromString($token->id),
        );

        $entity = $this->mapper->toEntity($token, $entity);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $this->mapper->toModel($entity);
    }

    public function findByHash(string $tokenHash): ?RefreshTokenModel
    {
        $entity = $this->entityManager
            ->getRepository(RefreshTokenEntity::class)
            ->findOneBy([RefreshTokenEntity::FIELD_TOKEN_HASH => $tokenHash]);

        return $entity !== null ? $this->mapper->toModel($entity) : null;
    }

    public function revokeIfActive(string $id, DateTimeImmutable $now): bool
    {
        $updated = $this->entityManager->createQueryBuilder()
            ->update(RefreshTokenEntity::class, 'rt')
            ->set('rt.'.RefreshTokenEntity::FIELD_REVOKED_AT, ':now')
            ->where('rt.'.RefreshTokenEntity::FIELD_ID.' = :id')
            ->andWhere('rt.'.RefreshTokenEntity::FIELD_REVOKED_AT.' IS NULL')
            ->andWhere('rt.'.RefreshTokenEntity::FIELD_EXPIRES_AT.' > :now')
            ->setParameter('id', Uuid::fromString($id), 'uuid')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();

        return $updated > 0;
    }

    public function revokeFamily(string $familyId, DateTimeImmutable $now): void
    {
        $this->entityManager->createQueryBuilder()
            ->update(RefreshTokenEntity::class, 'rt')
            ->set('rt.'.RefreshTokenEntity::FIELD_REVOKED_AT, ':now')
            ->where('rt.'.RefreshTokenEntity::FIELD_FAMILY_ID.' = :familyId')
            ->andWhere('rt.'.RefreshTokenEntity::FIELD_REVOKED_AT.' IS NULL')
            ->setParameter('familyId', Uuid::fromString($familyId), 'uuid')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }

    public function revokeAllForUser(string $userId, DateTimeImmutable $now): void
    {
        $this->entityManager->createQueryBuilder()
            ->update(RefreshTokenEntity::class, 'rt')
            ->set('rt.'.RefreshTokenEntity::FIELD_REVOKED_AT, ':now')
            ->where('rt.'.RefreshTokenEntity::FIELD_USER_ID.' = :userId')
            ->andWhere('rt.'.RefreshTokenEntity::FIELD_REVOKED_AT.' IS NULL')
            ->setParameter('userId', Uuid::fromString($userId), 'uuid')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }

    public function deleteAllForUser(string $userId): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(RefreshTokenEntity::class, 'rt')
            ->where('rt.'.RefreshTokenEntity::FIELD_USER_ID.' = :userId')
            ->setParameter('userId', Uuid::fromString($userId), 'uuid')
            ->getQuery()
            ->execute();
    }

    public function deleteExpiredBefore(DateTimeImmutable $threshold): int
    {
        $deleted = $this->entityManager->createQueryBuilder()
            ->delete(RefreshTokenEntity::class, 'rt')
            ->where('rt.'.RefreshTokenEntity::FIELD_EXPIRES_AT.' < :threshold')
            ->orWhere(sprintf(
                'rt.%s IS NOT NULL AND rt.%s < :threshold',
                RefreshTokenEntity::FIELD_REVOKED_AT,
                RefreshTokenEntity::FIELD_REVOKED_AT,
            ))
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();

        return is_int($deleted) ? $deleted : 0;
    }
}
