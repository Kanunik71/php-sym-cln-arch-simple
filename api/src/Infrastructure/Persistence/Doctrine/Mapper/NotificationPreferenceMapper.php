<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Application\Model\Notification\NotificationPreferenceModel;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\UserNotificationPreferenceEntity;
use App\Infrastructure\Persistence\Doctrine\EntityReferenceAdapter;
use App\Shared\Utils\UidUtils;
use Symfony\Component\Uid\Uuid;

final class NotificationPreferenceMapper
{
    public function __construct(
        private readonly EntityReferenceAdapter $entityReference,
    ) {
    }

    public function toModel(UserNotificationPreferenceEntity $entity): NotificationPreferenceModel
    {
        return new NotificationPreferenceModel(
            id: UidUtils::toString($entity->getId()),
            ownerId: UidUtils::toString($entity->getUserId()),
            type: NotificationTypeEnum::from($entity->getType()),
            enabled: $entity->isEnabled(),
            emailEnabled: $entity->isEmailEnabled(),
            smsEnabled: $entity->isSmsEnabled(),
        );
    }

    /**
     * @param list<UserNotificationPreferenceEntity> $entities
     *
     * @return list<NotificationPreferenceModel>
     */
    public function toModelArray(array $entities): array
    {
        return array_map(
            fn (UserNotificationPreferenceEntity $entity): NotificationPreferenceModel => $this->toModel($entity),
            $entities,
        );
    }

    public function toEntity(
        NotificationPreferenceModel $preference,
        ?UserNotificationPreferenceEntity $entity = null,
    ): UserNotificationPreferenceEntity {
        $entity ??= new UserNotificationPreferenceEntity();
        $entity->setId(Uuid::fromString($preference->id));

        /** @var UserEntity $user */
        $user = $this->entityReference->getReference(
            UserEntity::class,
            Uuid::fromString($preference->ownerId),
        );

        $entity
            ->setUser($user)
            ->setType($preference->type->value)
            ->setEnabled($preference->enabled)
            ->setEmailEnabled($preference->emailEnabled)
            ->setSmsEnabled($preference->smsEnabled);

        return $entity;
    }
}
