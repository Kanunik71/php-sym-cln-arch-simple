<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Utils;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use LogicException;

final class DoctrineCollectionAssertUtils
{
    /**
     * PersistentCollection must already be initialized (no lazy follow-up).
     * ArrayCollection / non-persistent collections are treated as loaded.
     * Empty initialized collections are fine.
     *
     * Collection value type is invariant; accept any Collection via templates.
     *
     * @template TKey of array-key
     * @template T
     *
     * @param Collection<TKey, T> $collection
     */
    public static function assertInitialized(Collection $collection, string $relation): void
    {
        if ($collection instanceof PersistentCollection && !$collection->isInitialized()) {
            throw new LogicException(sprintf(
                'Doctrine relation "%s" must be loaded before mapping to Domain; use an EntityFetcher / fetch-join.',
                $relation,
            ));
        }
    }
}
