<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query\User;

use App\Application\Enum\Common\FilterOperatorEnum;
use App\Application\Model\Common\Filter\DateTimeFilterModel;
use App\Application\Model\Common\Filter\IntFilterModel;
use App\Application\Model\Common\Filter\StringFilterModel;
use App\Infrastructure\Persistence\Doctrine\Entity\TaskUserEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * Relation filter: users assigned to a task via task_users.
 */
final readonly class UserTaskIdFilterApplier
{
    private const JOIN_ALIAS = 'tu';

    public function apply(
        QueryBuilder $qb,
        string $alias,
        StringFilterModel|IntFilterModel|DateTimeFilterModel $filter,
    ): void {
        if (!$filter instanceof StringFilterModel) {
            throw new InvalidArgumentException('Filter "taskId" must be a string filter.');
        }

        if (!in_array(self::JOIN_ALIAS, $qb->getAllAliases(), true)) {
            $qb->innerJoin(
                sprintf('%s.%s', $alias, UserEntity::FIELD_TASK_USERS),
                self::JOIN_ALIAS,
            );
            $qb->distinct();
        }

        $param = sprintf('taskId_%s', str_replace('.', '', uniqid('', true)));

        match ($filter->operator) {
            FilterOperatorEnum::Eq => $qb
                ->andWhere(sprintf('%s.%s = :%s', self::JOIN_ALIAS, TaskUserEntity::FIELD_TASK, $param))
                ->setParameter($param, Uuid::fromString($filter->value ?? ''), 'uuid'),
            FilterOperatorEnum::In => $qb
                ->andWhere(sprintf('%s.%s IN (:%s)', self::JOIN_ALIAS, TaskUserEntity::FIELD_TASK, $param))
                ->setParameter(
                    $param,
                    array_map(
                        static fn (string $id): Uuid => Uuid::fromString($id),
                        $filter->values ?? [],
                    ),
                ),
            default => throw new InvalidArgumentException(sprintf('Operator "%s" is not supported for filter "taskId".', $filter->operator->value)),
        };
    }
}
