<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Application\Model\Notification\NotificationPreferenceModel;
use App\Application\Port\Notification\NotificationPreferenceRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Mapper\NotificationPreferenceMapper;
use App\Infrastructure\Persistence\Doctrine\Query\Notification\NotificationPreferenceEntityFetcher;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final readonly class DoctrineNotificationPreferenceRepository implements NotificationPreferenceRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationPreferenceMapper $mapper,
        private NotificationPreferenceEntityFetcher $entityFetcher,
    ) {
    }

    public function save(NotificationPreferenceModel $preference): NotificationPreferenceModel
    {
        $entity = $this->entityFetcher->findById($preference->id);

        $entity = $this->mapper->toEntity($preference, $entity);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $reloaded = $this->entityFetcher->findById($preference->id);
        if ($reloaded === null) {
            throw new LogicException(sprintf(
                'NotificationPreference "%s" missing after save.',
                $preference->id,
            ));
        }

        return $this->mapper->toModel($reloaded);
    }

    public function findByOwnerIdAndType(string $ownerId, NotificationTypeEnum $type): ?NotificationPreferenceModel
    {
        $entity = $this->entityFetcher->findByOwnerIdAndType($ownerId, $type);

        return $entity !== null ? $this->mapper->toModel($entity) : null;
    }

    public function listByOwnerId(string $ownerId): array
    {
        return $this->mapper->toModelArray($this->entityFetcher->listByOwnerId($ownerId));
    }

    public function delete(NotificationPreferenceModel $preference): void
    {
        $entity = $this->entityFetcher->findById($preference->id);

        if ($entity === null) {
            return;
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}
