<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Port\TransactionManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function run(callable $operation): mixed
    {
        return $this->entityManager->wrapInTransaction(
            static function (EntityManagerInterface $_) use ($operation): mixed {
                return $operation();
            },
        );
    }
}
