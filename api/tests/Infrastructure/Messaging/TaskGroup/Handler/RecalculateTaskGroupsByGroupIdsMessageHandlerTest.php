<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Messaging\TaskGroup\Handler;

use App\Application\Cache\TaskGroup\TaskGroupSummaryCache;
use App\Application\Event\TaskGroup\TaskGroupRecalculationByGroupIdsRequestedEvent;
use App\Application\Port\CacheInterface;
use App\Application\Port\LockHandleInterface;
use App\Application\Port\TaskGroup\TaskGroupLockInterface;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Application\Service\TaskGroup\Lifecycle\TaskGroupRecalculationService;
use App\Infrastructure\Messaging\TaskGroup\Handler\RecalculateTaskGroupsByGroupIdsMessageHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RecalculateTaskGroupsByGroupIdsMessageHandlerTest extends TestCase
{
    private const GROUP_IDS = ['group-1', 'group-2'];

    public function testAcquiresLockByGroupIdsAndDelegatesToService(): void
    {
        $repository = $this->createMock(TaskGroupRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('listByIds')
            ->with(self::GROUP_IDS)
            ->willReturn([]);

        $lock = $this->createMock(TaskGroupLockInterface::class);
        $lock
            ->expects($this->once())
            ->method('acquireByGroupIds')
            ->with(self::GROUP_IDS)
            ->willReturn($this->createLockHandle());

        ($this->handler($repository, $lock))($this->event());
    }

    public function testDoesNotCallServiceWhenChannelIsLocked(): void
    {
        $repository = $this->createMock(TaskGroupRepositoryInterface::class);
        $repository->expects($this->never())->method('listByIds');

        $lock = $this->createMock(TaskGroupLockInterface::class);
        $lock->method('acquireByGroupIds')->willReturn(null);

        ($this->handler($repository, $lock))($this->event());
    }

    public function testPassesCallbackThatRefreshesLockToService(): void
    {
        $repository = $this->createMock(TaskGroupRepositoryInterface::class);
        $repository->method('listByIds')->willReturn([]);

        $handle = $this->createLockHandle();
        $handle->expects($this->atLeastOnce())->method('refresh');

        $lock = $this->createMock(TaskGroupLockInterface::class);
        $lock->method('acquireByGroupIds')->willReturn($handle);

        ($this->handler($repository, $lock))($this->event());
    }

    public function testReleasesLockAfterRecalculation(): void
    {
        $repository = $this->createMock(TaskGroupRepositoryInterface::class);
        $repository->method('listByIds')->willReturn([]);

        $handle = $this->createLockHandle();
        $handle->expects($this->once())->method('release');

        $lock = $this->createMock(TaskGroupLockInterface::class);
        $lock->method('acquireByGroupIds')->willReturn($handle);

        ($this->handler($repository, $lock))($this->event());
    }

    public function testReleasesLockWhenServiceFails(): void
    {
        $repository = $this->createMock(TaskGroupRepositoryInterface::class);
        $repository->method('listByIds')->willThrowException(new RuntimeException('recalculation failed'));

        $handle = $this->createLockHandle();
        $handle->expects($this->once())->method('release');

        $lock = $this->createMock(TaskGroupLockInterface::class);
        $lock->method('acquireByGroupIds')->willReturn($handle);

        $this->expectException(RuntimeException::class);

        ($this->handler($repository, $lock))($this->event());
    }

    private function event(): TaskGroupRecalculationByGroupIdsRequestedEvent
    {
        return new TaskGroupRecalculationByGroupIdsRequestedEvent(taskGroupIds: self::GROUP_IDS);
    }

    private function handler(
        TaskGroupRepositoryInterface $repository,
        TaskGroupLockInterface $lock,
    ): RecalculateTaskGroupsByGroupIdsMessageHandler {
        return new RecalculateTaskGroupsByGroupIdsMessageHandler(
            recalculationService: new TaskGroupRecalculationService(
                $repository,
                new TaskGroupSummaryCache($this->createMock(CacheInterface::class)),
            ),
            taskGroupLock: $lock,
        );
    }

    private function createLockHandle(): LockHandleInterface&MockObject
    {
        return $this->createMock(LockHandleInterface::class);
    }
}
