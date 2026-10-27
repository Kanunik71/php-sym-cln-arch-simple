<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\User\Action;

use App\Application\Service\Auth\RefreshTokenService;
use App\Application\Service\User\Action\UserLoginService;
use App\Application\Service\User\Action\UserRefreshService;
use App\Application\Service\User\Action\UserUpdateService;
use App\Application\Model\User\Action\UserLoginModel;
use App\Application\Model\User\Action\UserRefreshModel;
use App\Application\Model\User\Action\UserUpdateModel;
use App\Application\Model\User\UserLocationModel;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Application\Event\User\UserUpdatedEvent;
use App\Application\Exception\User\UnauthorizedUserAccessException;
use App\Application\Exception\User\UserAlreadyExistsException;
use App\Application\Exception\User\UserNotFoundException;
use App\Shared\Utils\UidUtils;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\TestBed;
use App\Tests\Support\Trait\Common\AssertMessengerTrait;
use App\Tests\Support\Trait\Model\AssertsUserModelTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

final class UserUpdateServiceTest extends DatabaseTestCase
{
    use AssertsUserModelTrait;
    use AssertMessengerTrait;

    public function testUpdatesOwnProfile(): void
    {
        $user = $this->bed->createUser(
            email: 'john@example.com',
            fname: 'John',
            lname: 'Doe',
            city: 'Paris',
        );

        $update = new UserUpdateModel(
            fname: 'Jane',
            lname: 'Smith',
            email: $user->getEmail()->toString(),
            location: new UserLocationModel(city: 'Berlin'),
            avatarFileId: $user->getAvatarFileId(),
            phone: '+79001234567',
        );

        $updateUser = $this->bed->get(UserUpdateService::class);

        $result = $updateUser->execute(
            $update,
            userId: $user->getId(),
            currentUserId: $user->getId(),
        );

        $this->assertSame($update->fname, $result->fname);
        $this->assertSame($update->lname, $result->lname);
        $this->assertSame($update->location?->city, $result->location?->city);
        $this->assertSame($user->getEmail()->toString(), $result->email);
        $this->assertSame('+79001234567', $result->phone);

        $this->bed->clear();
        $persisted = $this->bed->findUserById($user->getId());
        $this->assertNotNull($persisted);
        $this->assertUserModelMatches($persisted, $result);

        $this->assertDispatched(
            UserUpdatedEvent::class,
            predicate: static fn (UserUpdatedEvent $event): bool => $event->userId === $user->getId(),
        );
    }

    public function testClearsOptionalFields(): void
    {
        $user = $this->bed->createUser(
            email: 'john@example.com',
            fname: 'John',
            lname: 'Doe',
            city: 'Paris',
        );

        $update = new UserUpdateModel(
            fname: null,
            lname: null,
            email: $user->getEmail()->toString(),
            location: null,
            avatarFileId: null,
        );

        $updateUser = $this->bed->get(UserUpdateService::class);

        $result = $updateUser->execute(
            $update,
            userId: $user->getId(),
            currentUserId: $user->getId(),
        );

        $this->assertNull($result->fname);
        $this->assertNull($result->lname);
        $this->assertNull($result->location);
    }

    public function testUpdatesPassword(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');

        $updateUser = $this->bed->get(UserUpdateService::class);

        $updateUser->execute(
            new UserUpdateModel(
                fname: $user->getFname(),
                lname: $user->getLname(),
                email: $user->getEmail()->toString(),
                password: 'new-secret123',
                avatarFileId: $user->getAvatarFileId(),
            ),
            userId: $user->getId(),
            currentUserId: $user->getId(),
        );

        $loginUser = $this->bed->get(UserLoginService::class);
        $loginResult = $loginUser->execute(new UserLoginModel(
            email: 'john@example.com',
            password: 'new-secret123',
        ));

        $this->assertNotSame('', $loginResult->token);
    }

    public function testPasswordChangeRollsBackWhenFlushFails(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $userId = $user->getId();
        $originalHash = $user->getPasswordHash();
        $issued = $this->bed->get(RefreshTokenService::class)->issue($userId);

        $entityManager = $this->bed->get(EntityManagerInterface::class);
        $entityManager->getEventManager()->addEventListener(Events::postFlush, new class($userId) {
            private int $flushes = 0;

            public function __construct(private string $userId)
            {
            }

            public function postFlush(PostFlushEventArgs $args): void
            {
                $this->flushes++;

                if ($this->flushes >= 2) {
                    throw new RuntimeException('forced rollback');
                }

                $entity = $args->getObjectManager()->find(UserEntity::class, Uuid::fromString($this->userId));

                if ($entity instanceof UserEntity) {
                    $entity->setFname('rollback-marker');
                }
            }
        });

        try {
            $this->bed->get(UserUpdateService::class)->execute(
                new UserUpdateModel(
                    fname: $user->getFname(),
                    lname: $user->getLname(),
                    email: $user->getEmail()->toString(),
                    password: 'new-secret123',
                    avatarFileId: $user->getAvatarFileId(),
                ),
                userId: $userId,
                currentUserId: $userId,
            );
            $this->fail('Password change must fail so the transaction can roll back.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced rollback', $exception->getMessage());
        }

        self::ensureKernelShutdown();
        self::bootKernel();
        $this->bed = TestBed::create(static::getContainer());

        $persisted = $this->bed->findUserById($userId);
        $this->assertNotNull($persisted);
        $this->assertSame($originalHash, $persisted->getPasswordHash());

        $refreshed = $this->bed->get(UserRefreshService::class)->execute(
            new UserRefreshModel(refreshToken: $issued->token),
        );
        $this->assertNotSame('', $refreshed->token);
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');

        $updateUser = $this->bed->get(UserUpdateService::class);

        $this->expectException(UserNotFoundException::class);

        $updateUser->execute(
            new UserUpdateModel(
                fname: 'Jane',
                lname: $user->getLname(),
                email: $user->getEmail()->toString(),
                avatarFileId: $user->getAvatarFileId(),
            ),
            userId: UidUtils::nil(),
            currentUserId: UidUtils::nil(),
        );
    }

    public function testThrowsWhenUpdatingAnotherUser(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $other = $this->bed->createUser(email: 'other@example.com');

        $updateUser = $this->bed->get(UserUpdateService::class);

        $this->expectException(UnauthorizedUserAccessException::class);

        $updateUser->execute(
            new UserUpdateModel(
                fname: 'Hacked',
                lname: $user->getLname(),
                email: $user->getEmail()->toString(),
                avatarFileId: $user->getAvatarFileId(),
            ),
            userId: $user->getId(),
            currentUserId: $other->getId(),
        );
    }

    public function testThrowsWhenEmailAlreadyExists(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $this->bed->createUser(email: 'taken@example.com');

        $updateUser = $this->bed->get(UserUpdateService::class);

        $this->expectException(UserAlreadyExistsException::class);

        $updateUser->execute(
            new UserUpdateModel(
                fname: $user->getFname(),
                lname: $user->getLname(),
                email: 'taken@example.com',
                avatarFileId: $user->getAvatarFileId(),
            ),
            userId: $user->getId(),
            currentUserId: $user->getId(),
        );
    }
}
