<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Messaging\TaskGroup\Handler;

use App\Application\Cache\TaskGroup\TaskGroupSummaryCache;
use App\Application\Enum\TaskGroup\TaskGroupStatusEnum;
use App\Application\Event\TaskGroup\TaskGroupRecalculationByTaskRequestedEvent;
use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Application\Port\CacheInterface;
use App\Application\Port\LockHandleInterface;
use App\Application\Port\TaskGroup\TaskGroupLockInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Application\Service\TaskGroup\Lifecycle\TaskGroupRecalculationService;
use App\Infrastructure\Messaging\TaskGroup\Handler\RecalculateTaskGroupsByTaskMessageHandler;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RecalculateTaskGroupsByTaskMessageHandlerTest extends TestCase
{
    private const TASK_ID = 'task-1';

    public function testAcquiresLockByTaskIdAndDelegatesToService(): void
    {
        $repository = $this->createMock(TaskGroupRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('listByTaskId')
            ->with(self::TASK_ID)
            ->willReturn([]);

        $lock = $this->createMock(TaskGroupLockInterface::class);
        $lock
            ->expects($this->once())
            ->method('acquireByTaskId')
            ->with(self::TASK_ID)
            ->willReturn($this->createLockHandle());

        ($this->handler($repository, $lock))($this->event());
    }

    public function testDoesNotCallServiceWhenChannelIsLocked(): void
    {
        $repository = $this->createMock(TaskGroupRepositoryInterface::class);
        $repository->expects($this->never())->method('listByTaskId');

        $lock = $this->createMock(TaskGroupLockInterface::class);
        $lock->method('acquireByTaskId')->willReturn(null);

        ($this->handler($repository, $lock))($this->event());
    }

    public function testPassesCallbackThatRefreshesLockToService(): void
    {
        $repository = $this->createMock(TaskGroupRepositoryInterface::class);
        $repository->method('listByTaskId')->willReturn([$this->taskGroup()]);
        $repository->method('listMembershipFactsByIds')->willReturn([]);

        $handle = $this->createLockHandle();
        $handle->expects($this->atLeastOnce())->method('refresh');

        $lock = $this->createMock(TaskGroupLockInterface::class);
        $lock->method('acquireByTaskId')->willReturn($handle);

        ($this->handler($repository, $lock))($this->event());
    }

    public function testReleasesLockAfterRecalculation(): void
    {
        $repository = $this->createMock(TaskGroupRepositoryInterface::class);
        $repository->method('listByTaskId')->willReturn([]);

        $handle = $this->createLockHandle();
        $handle->expects($this->once())->method('release');

        $lock = $this->createMock(TaskGroupLockInterface::class);
        $lock->method('acquireByTaskId')->willReturn($handle);

        ($this->handler($repository, $lock))($this->event());
    }

    public function testReleasesLockWhenServiceFails(): void
    {
        $repository = $this->createMock(TaskGroupRepositoryInterface::class);
        $repository->method('listByTaskId')->willThrowException(new RuntimeException('recalculation failed'));

        $handle = $this->createLockHandle();
        $handle->expects($this->once())->method('release');

        $lock = $this->createMock(TaskGroupLockInterface::class);
        $lock->method('acquireByTaskId')->willReturn($handle);

        $this->expectException(RuntimeException::class);

        ($this->handler($repository, $lock))($this->event());
    }

    private function event(): TaskGroupRecalculationByTaskRequestedEvent
    {
        return new TaskGroupRecalculationByTaskRequestedEvent(taskId: self::TASK_ID);
    }

    private function taskGroup(): TaskGroupModel
    {
        return new TaskGroupModel(
            id: 'group-1',
            ownerId: 'owner-1',
            name: 'Release',
            status: TaskGroupStatusEnum::Initial,
            createdAt: new DateTimeImmutable(),
        );
    }

    private function handler(
        TaskGroupRepositoryInterface $repository,
        TaskGroupLockInterface $lock,
    ): RecalculateTaskGroupsByTaskMessageHandler {
        return new RecalculateTaskGroupsByTaskMessageHandler(
            recalculationService: new TaskGroupRecalculationService(
                $repository,
                new TaskGroupSummaryCache($this->createMock(CacheInterface::class)),
            ),
            taskGroupLock: $lock,
        );
    }

    private function createLockHandle(): LockHandleInterface
    {
        return $this->createMock(LockHandleInterface::class);
    }
}
