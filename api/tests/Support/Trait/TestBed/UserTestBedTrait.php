<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait\TestBed;

use App\Application\Port\Notification\NotificationPreferenceRepositoryInterface;
use App\Application\Port\User\UserRepositoryInterface;
use App\Application\ValueObject\Common\Email;
use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Application\Model\User\UserModel;
use App\Application\Model\Notification\NotificationPreferenceModel;
use App\Shared\Utils\UidUtils;
use App\Tests\Support\TestBed;
use DateTimeImmutable;

trait UserTestBedTrait
{
    public function userRepository(): UserRepositoryInterface
    {
        return $this->users;
    }

    public function notificationPreferenceRepository(): NotificationPreferenceRepositoryInterface
    {
        return $this->notificationPreferences;
    }

    public function createUser(
        ?string $email = null,
        ?string $password = null,
        ?string $fname = null,
        ?string $lname = null,
        ?string $city = null,
        ?string $id = null,
        ?string $passwordHash = null,
        ?DateTimeImmutable $createdAt = null,
        ?string $phone = null,
        bool $withDefaultNotificationPreferences = true,
    ): UserModel {
        $now = $createdAt ?? new DateTimeImmutable();
        $user = new UserModel(
            id: $id ?? UidUtils::generateString(),
            fname: $fname,
            lname: $lname,
            email: $email ?? ('user-'.UidUtils::generateString().'@example.com'),
            passwordHash: $passwordHash ?? $this->passwordHasher->hash($password ?? TestBed::DEMO_PASSWORD),
            createdAt: $now,
            updatedAt: $now,
            city: $city,
            phone: $phone,
        );

        $saved = $this->users->save($user);

        if ($withDefaultNotificationPreferences) {
            $this->notificationPreferences->save(
                NotificationPreferenceModel::createDefaultUserUpdated($saved->getId()),
            );
        }

        return $saved;
    }

    public function findUserByEmail(string $email): ?UserModel
    {
        return $this->users->findByEmail(new Email($email));
    }

    public function findUserById(string $id): ?UserModel
    {
        return $this->users->findById($id);
    }

    public function findNotificationPreference(string $ownerId, NotificationTypeEnum $type): ?NotificationPreferenceModel
    {
        return $this->notificationPreferences->findByOwnerIdAndType($ownerId, $type);
    }
}
