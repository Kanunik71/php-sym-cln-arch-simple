<?php

declare(strict_types=1);

namespace App\Application\Model\User;

use App\Application\Enum\User\UserFilterFieldEnum;
use App\Application\Model\Common\ArrayableModelListInterface;
use App\Application\Model\Common\Filter\DateTimeFilterModel;
use App\Application\Model\Common\Filter\IntFilterModel;
use App\Application\Model\Common\Filter\StringFilterModel;
use App\Shared\Utils\Filter\FilterCriteriaUtils;
use App\Shared\Utils\Filter\FilterFieldUtils;

final readonly class UserFilterCriterionModel implements ArrayableModelListInterface
{
    public function __construct(
        public UserFilterFieldEnum $field,
        public StringFilterModel|IntFilterModel|DateTimeFilterModel $filter,
    ) {
    }

    /**
     * @param array<string, mixed> $data HTTP payload; reads optional `filter` object
     *
     * @return list<static>
     */
    public static function fromArrayList(array $data): array
    {
        return FilterCriteriaUtils::fromArray(
            $data,
            static function (string $fieldName, array $operatorMap): self {
                $field = FilterFieldUtils::isAllowed($fieldName, UserFilterFieldEnum::class);

                // Entity-specific filters: match ($field) { ... default => common type }.
                $filter = match ($field) {
                    default => $field->type()->fromOperatorMap($operatorMap, $fieldName),
                };

                return new self(
                    field: $field,
                    filter: $filter,
                );
            },
        );
    }

    /**
     * @param list<static> $items
     *
     * @return array<string, mixed>
     */
    public static function toArrayList(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $filter = [];
        foreach ($items as $item) {
            $filter[$item->field->value] = $item->filter->toOperatorMap();
        }

        return ['filter' => $filter];
    }
}
