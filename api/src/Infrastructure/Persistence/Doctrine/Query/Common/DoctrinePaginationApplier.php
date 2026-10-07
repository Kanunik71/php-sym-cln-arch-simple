<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query\Common;

use App\Application\Enum\Common\SortDirectionEnum;
use App\Application\Model\Common\PaginationModel;
use Doctrine\ORM\QueryBuilder;

final class DoctrinePaginationApplier
{
    public function applyLimit(QueryBuilder $qb, PaginationModel $pagination): void
    {
        $qb->setFirstResult($pagination->offset())
            ->setMaxResults($pagination->perPage);
    }

    public function applySort(
        QueryBuilder $qb,
        string $path,
        SortDirectionEnum $direction = SortDirectionEnum::Desc,
    ): void {
        $qb->orderBy($path, strtoupper($direction->value));
    }
}
