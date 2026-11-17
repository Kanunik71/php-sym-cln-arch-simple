<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Lock;

use App\Application\Port\LockHandleInterface;
use App\Application\Port\TaskGroup\TaskGroupLockInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TaskGroupLockTest extends KernelTestCase
{
    public function testSecondAcquireOfSameTaskIsBlocked(): void
    {
        $lock = $this->lock();

        $handle = $lock->acquireByTaskId('task-1');
        $this->assertNotNull($handle);

        $this->assertTrue($this->isBusy(fn (): ?LockHandleInterface => $lock->acquireByTaskId('task-1')));

        $handle->release();
    }

    public function testLockIsReleasedAfterHandleRelease(): void
    {
        $lock = $this->lock();

        $handle = $lock->acquireByTaskId('task-1');
        $this->assertNotNull($handle);

        $handle->release();

        $this->assertFalse($this->isBusy(fn (): ?LockHandleInterface => $lock->acquireByTaskId('task-1')));
    }

    public function testGroupScopeIgnoresOrderAndDuplicates(): void
    {
        $lock = $this->lock();

        $handle = $lock->acquireByGroupIds(['group-1', 'group-2']);
        $this->assertNotNull($handle);

        $this->assertTrue(
            $this->isBusy(fn (): ?LockHandleInterface => $lock->acquireByGroupIds(['group-2', 'group-1', 'group-1'])),
        );

        $handle->release();
    }

    public function testTaskAndGroupChannelsAreIndependent(): void
    {
        $lock = $this->lock();

        $taskHandle = $lock->acquireByTaskId('task-1');
        $groupHandle = $lock->acquireByGroupIds(['group-1']);

        $this->assertNotNull($taskHandle);
        $this->assertNotNull($groupHandle);

        $taskHandle->release();
        $groupHandle->release();
    }

    public function testEmptyGroupIdsAreNotLocked(): void
    {
        $this->assertNull($this->lock()->acquireByGroupIds([]));
    }

    public function testRefreshKeepsLockOwned(): void
    {
        $lock = $this->lock();

        $handle = $lock->acquireByTaskId('task-1');
        $this->assertNotNull($handle);

        $handle->refresh();

        $this->assertTrue($this->isBusy(fn (): ?LockHandleInterface => $lock->acquireByTaskId('task-1')));

        $handle->release();
    }

    /**
     * true — канал занят (повторный захват не удался).
     *
     * @param callable(): ?LockHandleInterface $acquire
     */
    private function isBusy(callable $acquire): bool
    {
        $handle = $acquire();

        if ($handle === null) {
            return true;
        }

        $handle->release();

        return false;
    }

    private function lock(): TaskGroupLockInterface
    {
        $lock = static::getContainer()->get(TaskGroupLockInterface::class);
        self::assertInstanceOf(TaskGroupLockInterface::class, $lock);

        return $lock;
    }
}
