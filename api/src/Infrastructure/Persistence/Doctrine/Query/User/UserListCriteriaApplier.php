<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query\User;

use App\Application\Enum\User\UserFilterFieldEnum;
use App\Application\Model\User\UserFilterCriterionModel;
use App\Infrastructure\Persistence\Doctrine\Query\Common\DoctrineFilterApplier;
use Doctrine\ORM\QueryBuilder;

final readonly class UserListCriteriaApplier
{
    public function __construct(
        private DoctrineFilterApplier $filters,
        private UserTaskIdFilterApplier $taskIdFilter,
    ) {
    }

    /**
     * @param list<UserFilterCriterionModel> $criteria
     */
    public function apply(QueryBuilder $qb, array $criteria, string $alias = 'u'): void
    {
        foreach ($criteria as $item) {
            match ($item->field) {
                UserFilterFieldEnum::TaskId => $this->taskIdFilter->apply($qb, $alias, $item->filter),
                default => $this->filters->apply(
                    $qb,
                    UserFilterFieldDoctrineMap::path($item->field, $alias),
                    $item->field->value,
                    $item->filter,
                ),
            };
        }
    }
}
