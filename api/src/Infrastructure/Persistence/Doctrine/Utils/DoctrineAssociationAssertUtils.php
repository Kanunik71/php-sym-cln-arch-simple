<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Utils;

use Doctrine\Persistence\Proxy;
use LogicException;

final class DoctrineAssociationAssertUtils
{
    /**
     * Nullable/to-one association must already be loaded (no lazy follow-up).
     * null is fine; uninitialized Doctrine proxy is not.
     */
    public static function assertInitialized(?object $association, string $relation): void
    {
        if ($association instanceof Proxy && !$association->__isInitialized()) {
            throw new LogicException(sprintf(
                'Doctrine relation "%s" must be loaded before mapping to Domain; use an EntityFetcher / fetch-join.',
                $relation,
            ));
        }
    }
}
