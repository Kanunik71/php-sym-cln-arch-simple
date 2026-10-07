<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final readonly class EntityReferenceAdapter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function getReference(string $class, mixed $id): object
    {
        $reference = $this->entityManager->getReference($class, $id);
        if (!$reference instanceof $class) {
            throw new LogicException(sprintf('Could not get Doctrine reference for %s.', $class));
        }

        return $reference;
    }
}
