<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Query\Common;

use App\Application\Enum\Common\FilterOperatorEnum;
use App\Application\Model\Common\Filter\DateTimeFilterModel;
use App\Application\Model\Common\Filter\IntFilterModel;
use App\Application\Model\Common\Filter\StringFilterModel;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;

/**
 * Shared operator → DQL mapping for list/search QueryBuilders.
 * Entity appliers pass concrete paths; they do not duplicate match arms.
 */
final class DoctrineFilterApplier
{
    public function apply(
        QueryBuilder $qb,
        string $path,
        string $paramBase,
        StringFilterModel|IntFilterModel|DateTimeFilterModel $filter,
    ): void {
        match (true) {
            $filter instanceof StringFilterModel => $this->applyString($qb, $path, $paramBase, $filter),
            $filter instanceof IntFilterModel => $this->applyInt($qb, $path, $paramBase, $filter),
            $filter instanceof DateTimeFilterModel => $this->applyDateTime($qb, $path, $paramBase, $filter),
        };
    }

    public function applyString(
        QueryBuilder $qb,
        string $path,
        string $paramBase,
        ?StringFilterModel $filter,
    ): void {
        if ($filter === null) {
            return;
        }

        $param = $this->paramName($paramBase);

        match ($filter->operator) {
            FilterOperatorEnum::Eq => $qb
                ->andWhere(sprintf('%s = :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::Ne => $qb
                ->andWhere(sprintf('%s != :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::Like => $qb
                ->andWhere(sprintf('%s LIKE :%s', $path, $param))
                ->setParameter($param, $this->likeValue($filter->value ?? '')),
            FilterOperatorEnum::In => $qb
                ->andWhere(sprintf('%s IN (:%s)', $path, $param))
                ->setParameter($param, $filter->values ?? []),
            FilterOperatorEnum::IsNull => $qb->andWhere(sprintf('%s IS NULL', $path)),
            FilterOperatorEnum::IsNotNull => $qb->andWhere(sprintf('%s IS NOT NULL', $path)),
            default => throw new InvalidArgumentException(sprintf('Operator "%s" is not supported for string path "%s".', $filter->operator->value, $path)),
        };
    }

    public function applyInt(
        QueryBuilder $qb,
        string $path,
        string $paramBase,
        ?IntFilterModel $filter,
    ): void {
        if ($filter === null) {
            return;
        }

        $param = $this->paramName($paramBase);

        match ($filter->operator) {
            FilterOperatorEnum::Eq => $qb
                ->andWhere(sprintf('%s = :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::Ne => $qb
                ->andWhere(sprintf('%s != :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::Gt => $qb
                ->andWhere(sprintf('%s > :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::Gte => $qb
                ->andWhere(sprintf('%s >= :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::Lt => $qb
                ->andWhere(sprintf('%s < :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::Lte => $qb
                ->andWhere(sprintf('%s <= :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::In => $qb
                ->andWhere(sprintf('%s IN (:%s)', $path, $param))
                ->setParameter($param, $filter->values ?? []),
            FilterOperatorEnum::IsNull => $qb->andWhere(sprintf('%s IS NULL', $path)),
            FilterOperatorEnum::IsNotNull => $qb->andWhere(sprintf('%s IS NOT NULL', $path)),
            default => throw new InvalidArgumentException(sprintf('Operator "%s" is not supported for int path "%s".', $filter->operator->value, $path)),
        };
    }

    public function applyDateTime(
        QueryBuilder $qb,
        string $path,
        string $paramBase,
        ?DateTimeFilterModel $filter,
    ): void {
        if ($filter === null) {
            return;
        }

        $param = $this->paramName($paramBase);

        match ($filter->operator) {
            FilterOperatorEnum::Eq => $qb
                ->andWhere(sprintf('%s = :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::Ne => $qb
                ->andWhere(sprintf('%s != :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::Gt => $qb
                ->andWhere(sprintf('%s > :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::Gte => $qb
                ->andWhere(sprintf('%s >= :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::Lt => $qb
                ->andWhere(sprintf('%s < :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::Lte => $qb
                ->andWhere(sprintf('%s <= :%s', $path, $param))
                ->setParameter($param, $filter->value),
            FilterOperatorEnum::IsNull => $qb->andWhere(sprintf('%s IS NULL', $path)),
            FilterOperatorEnum::IsNotNull => $qb->andWhere(sprintf('%s IS NOT NULL', $path)),
            default => throw new InvalidArgumentException(sprintf('Operator "%s" is not supported for datetime path "%s".', $filter->operator->value, $path)),
        };
    }

    private function paramName(string $base): string
    {
        $safe = preg_replace('/\W+/', '_', $base) ?? 'p';

        return sprintf('%s_%s', $safe, str_replace('.', '', uniqid('', true)));
    }

    private function likeValue(string $value): string
    {
        if (str_contains($value, '%') || str_contains($value, '_')) {
            return $value;
        }

        return '%'.$value.'%';
    }
}
