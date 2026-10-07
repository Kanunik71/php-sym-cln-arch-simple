<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\User\Action;

use App\Application\Service\User\Action\UserDeleteService;
use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Application\Enum\Task\TaskUserStatusEnum;
use App\Application\Model\Task\TaskUserModel;
use App\Application\Service\Task\Lifecycle\TaskUserListService;
use App\Application\Exception\User\UnauthorizedUserAccessException;
use App\Application\Exception\User\UserNotFoundException;
use App\Shared\Utils\UidUtils;
use App\Tests\DatabaseTestCase;
use App\Tests\Support\TestBed;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use RuntimeException;

final class UserDeleteServiceTest extends DatabaseTestCase
{
    public function testDeletesOwnAccount(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $deletedUserId = $user->getId();

        $deleteUser = $this->bed->get(UserDeleteService::class);

        $deleteUser->execute(
            userId: $deletedUserId,
            currentUserId: $deletedUserId,
        );

        $this->bed->clear();
        $this->assertNull($this->bed->findUserById($deletedUserId));
    }

    public function testDeletesOwnedAssetsTaskLinksPreferencesAndFiles(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $other = $this->bed->createUser(email: 'other@example.com');
        $deletedUserId = $user->getId();

        $task = $this->bed->createTask(
            name: 'Shared task',
            users: [
                TaskUserModel::create($deletedUserId, TaskUserStatusEnum::Initial),
                TaskUserModel::create($other->getId(), TaskUserStatusEnum::Initial),
            ],
        );
        $asset = $this->bed->createAssetForTask($task->getId(), $deletedUserId, 'Laptop', 999.99);
        $assetId = $asset->id;
        $taskId = $task->getId();

        $this->assertNotNull(
            $this->bed->findNotificationPreference($deletedUserId, NotificationTypeEnum::UserUpdated),
        );

        $deleteUser = $this->bed->get(UserDeleteService::class);

        $deleteUser->execute(
            userId: $deletedUserId,
            currentUserId: $deletedUserId,
        );

        $this->bed->clear();

        $this->assertNull($this->bed->findUserById($deletedUserId));
        $this->assertNull($this->bed->findAssetById($assetId));
        $this->assertNull(
            $this->bed->findNotificationPreference($deletedUserId, NotificationTypeEnum::UserUpdated),
        );

        $remainingTask = $this->bed->findTaskById($taskId);
        $this->assertNotNull($remainingTask);
        $remainingUsers = $this->bed->taskRepository()->listUsers($remainingTask->id);
        $this->assertFalse(TaskUserListService::has($remainingUsers, $deletedUserId));
        $this->assertTrue(TaskUserListService::has($remainingUsers, $other->getId()));
    }

    public function testRollsBackWhenDeleteFailsMidway(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $other = $this->bed->createUser(email: 'other@example.com');
        $userId = $user->getId();

        $task = $this->bed->createTask(
            name: 'Shared task',
            users: [
                TaskUserModel::create($userId, TaskUserStatusEnum::Initial),
                TaskUserModel::create($other->getId(), TaskUserStatusEnum::Initial),
            ],
        );
        $asset = $this->bed->createAssetForTask($task->getId(), $userId, 'Laptop', 999.99);
        $assetId = $asset->id;
        $taskId = $task->getId();

        $entityManager = $this->bed->get(EntityManagerInterface::class);
        $entityManager->getEventManager()->addEventListener(Events::postFlush, new class {
            private int $flushes = 0;

            public function postFlush(PostFlushEventArgs $args): void
            {
                $this->flushes++;
                if ($this->flushes >= 2) {
                    throw new RuntimeException('forced rollback');
                }
            }
        });

        $deleteUser = $this->bed->get(UserDeleteService::class);

        try {
            $deleteUser->execute(
                userId: $userId,
                currentUserId: $userId,
            );
            $this->fail('Delete must fail so the transaction can roll back.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced rollback', $exception->getMessage());
        }

        self::ensureKernelShutdown();
        self::bootKernel();
        $this->bed = TestBed::create(static::getContainer());

        $this->assertNotNull($this->bed->findUserById($userId));
        $this->assertNotNull($this->bed->findAssetById($assetId));
        $this->assertNotNull(
            $this->bed->findNotificationPreference($userId, NotificationTypeEnum::UserUpdated),
        );

        $remainingTask = $this->bed->findTaskById($taskId);
        $this->assertNotNull($remainingTask);
        $remainingUsers = $this->bed->taskRepository()->listUsers($remainingTask->id);
        $this->assertTrue(TaskUserListService::has($remainingUsers, $userId));
        $this->assertTrue(TaskUserListService::has($remainingUsers, $other->getId()));
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $deleteUser = $this->bed->get(UserDeleteService::class);

        $this->expectException(UserNotFoundException::class);

        $deleteUser->execute(
            userId: UidUtils::nil(),
            currentUserId: UidUtils::nil(),
        );
    }

    public function testThrowsWhenDeletingAnotherUser(): void
    {
        $user = $this->bed->createUser(email: 'john@example.com');
        $other = $this->bed->createUser(email: 'other@example.com');

        $deleteUser = $this->bed->get(UserDeleteService::class);

        $this->expectException(UnauthorizedUserAccessException::class);

        $deleteUser->execute(
            userId: $user->getId(),
            currentUserId: $other->getId(),
        );
    }
}
