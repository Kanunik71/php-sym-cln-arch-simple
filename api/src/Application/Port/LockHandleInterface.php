<?php

declare(strict_types=1);

namespace App\Application\Port;

/**
 * A held lock. Only its owner can release it.
 */
interface LockHandleInterface
{
    /**
     * Extends the lock so a long-running operation does not lose it.
     */
    public function refresh(): void;

    /**
     * Releases the lock. Safe to call in finally.
     */
    public function release(): void;
}
