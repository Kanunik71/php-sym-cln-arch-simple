<?php

declare(strict_types=1);

namespace App\Application\Port;

interface TransactionManagerInterface
{
    /**
     * Runs $operation inside a single DB transaction (all-or-nothing).
     * Nested repository flush() calls participate in the same transaction.
     *
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function run(callable $operation): mixed;
}
