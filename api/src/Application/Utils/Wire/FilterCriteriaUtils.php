<?php

declare(strict_types=1);

namespace App\Application\Utils\Wire;

use InvalidArgumentException;

/**
 * Generic HTTP `filter` bag parsing for Search*Query criteria.
 * Entity *FilterCriterionModel supplies the per-field builder and the field whitelist.
 */
final class FilterCriteriaUtils
{
    /**
     * @template TCriterion
     *
     * @param array<string, mixed>                               $data
     * @param callable(string, array<string, mixed>): TCriterion $createCriterion
     *
     * @return list<TCriterion>
     */
    public static function fromArray(array $data, callable $createCriterion): array
    {
        $rawFilters = $data['filter'] ?? null;
        if ($rawFilters === null) {
            return [];
        }

        if (!is_array($rawFilters)) {
            throw new InvalidArgumentException('Field "filter" must be an object.');
        }

        $filters = InputAssertUtils::stringKeyedArray($rawFilters, 'filter');
        $criteria = [];

        foreach ($filters as $fieldName => $operatorMap) {
            if (!is_array($operatorMap)) {
                throw new InvalidArgumentException(sprintf('Filter "%s" must be an object of {operator: value}.', $fieldName));
            }

            $criteria[] = $createCriterion(
                $fieldName,
                InputAssertUtils::stringKeyedArray($operatorMap, sprintf('filter.%s', $fieldName)),
            );
        }

        return $criteria;
    }
}
