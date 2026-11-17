<?php

declare(strict_types=1);

namespace App\Infrastructure\Lock;

use App\Application\Port\LockHandleInterface;
use Symfony\Component\Lock\LockInterface as SymfonyLock;

final readonly class SymfonyLockHandle implements LockHandleInterface
{
    public function __construct(
        private SymfonyLock $lock,
    ) {
    }

    public function refresh(): void
    {
        $this->lock->refresh();
    }

    public function release(): void
    {
        $this->lock->release();
    }
}
