<?php

declare(strict_types=1);

namespace App\Application\Service\Common;

use App\Application\Model\Common\Query\QueryDefaultSortMetaModel;
use App\Application\Model\Common\Query\QueryFilterFieldMetaModel;
use App\Application\Model\Common\Query\QueryPaginationMetaModel;
use App\Application\Model\Common\Query\SearchQueryMetaModel;
use App\Application\Enum\Common\SortDirectionEnum;
use App\Application\Model\Common\Query\FilterFieldMetaInterface;
use App\Application\Model\Common\PaginationModel;
use App\Shared\Utils\EnumUtils;
use BackedEnum;
use LogicException;

final readonly class SearchQueryMetaService
{
    /**
     * @param list<FilterFieldMetaInterface&BackedEnum> $filterFields
     * @param list<string>                              $sortFields
     */
    public function build(
        array $filterFields,
        array $sortFields,
        string $defaultSortField,
        SortDirectionEnum $defaultSortDirection,
    ): SearchQueryMetaModel {
        $filters = [];
        foreach ($filterFields as $field) {
            $fieldName = $field->value;
            if (!is_string($fieldName)) {
                throw new LogicException('Filter field meta must be a string-backed enum.');
            }

            /** @var list<string> $operators */
            $operators = EnumUtils::values($field->operators());

            $filters[] = new QueryFilterFieldMetaModel(
                field: $fieldName,
                type: $field->type()->value,
                operators: $operators,
            );
        }

        /** @var list<string> $sortDirections */
        $sortDirections = EnumUtils::caseValues(SortDirectionEnum::class);

        return new SearchQueryMetaModel(
            filters: $filters,
            sortFields: $sortFields,
            sortDirections: $sortDirections,
            defaultSort: new QueryDefaultSortMetaModel(
                field: $defaultSortField,
                direction: $defaultSortDirection->value,
            ),
            pagination: new QueryPaginationMetaModel(
                defaultPage: PaginationModel::DEFAULT_PAGE,
                defaultPerPage: PaginationModel::DEFAULT_PER_PAGE,
                maxPerPage: PaginationModel::MAX_PER_PAGE,
            ),
        );
    }
}
