<?php

declare(strict_types=1);

namespace App\Application\Model\Common\Query;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class SearchQueryMetaModel implements ArrayableModelInterface
{
    /**
     * @param list<QueryFilterFieldMetaModel> $filters
     * @param list<string>                    $sortFields
     * @param list<string>                    $sortDirections
     */
    public function __construct(
        public array $filters,
        public array $sortFields,
        public array $sortDirections,
        public QueryDefaultSortMetaModel $defaultSort,
        public QueryPaginationMetaModel $pagination,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            filters: InputAssertUtils::nestedModelList(
                $data['filters'] ?? null,
                'filters',
                QueryFilterFieldMetaModel::fromArray(...),
            ),
            sortFields: InputAssertUtils::stringList($data['sortFields'] ?? null, 'sortFields'),
            sortDirections: InputAssertUtils::stringList($data['sortDirections'] ?? null, 'sortDirections'),
            defaultSort: InputAssertUtils::requiredNestedModel(
                $data['defaultSort'] ?? null,
                'defaultSort',
                static fn (array $item): QueryDefaultSortMetaModel => QueryDefaultSortMetaModel::fromArray($item),
            ),
            pagination: InputAssertUtils::requiredNestedModel(
                $data['pagination'] ?? null,
                'pagination',
                static fn (array $item): QueryPaginationMetaModel => QueryPaginationMetaModel::fromArray($item),
            ),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'filters' => array_map(
                static fn (QueryFilterFieldMetaModel $item): array => $item->toArray(),
                $this->filters,
            ),
            'sortFields' => $this->sortFields,
            'sortDirections' => $this->sortDirections,
            'defaultSort' => $this->defaultSort->toArray(),
            'pagination' => $this->pagination->toArray(),
        ];
    }
}
