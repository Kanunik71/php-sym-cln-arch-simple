<?php

declare(strict_types=1);

namespace App\Application\Port;

/**
 * Удерживаемый лок. Освобождается только владельцем.
 */
interface LockHandleInterface
{
    /**
     * Продлевает лок, чтобы длинная операция не потеряла его.
     */
    public function refresh(): void;

    /**
     * Снимает лок. Безопасно вызывать в finally.
     */
    public function release(): void;
}
