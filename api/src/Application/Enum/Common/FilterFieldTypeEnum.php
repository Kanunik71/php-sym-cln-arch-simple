<?php

declare(strict_types=1);

namespace App\Application\Enum\Common;

use App\Application\Model\Common\Filter\DateTimeFilterModel;
use App\Application\Model\Common\Filter\IntFilterModel;
use App\Application\Model\Common\Filter\StringFilterModel;

enum FilterFieldTypeEnum: string
{
    case String = 'string';
    case Int = 'int';
    case DateTime = 'datetime';

    /**
     * @return list<FilterOperatorEnum>
     */
    public function operators(): array
    {
        return match ($this) {
            self::String => FilterOperatorEnum::forString(),
            self::Int => FilterOperatorEnum::forInt(),
            self::DateTime => FilterOperatorEnum::forDateTime(),
        };
    }

    /**
     * @param array<string, mixed> $operatorMap
     */
    public function fromOperatorMap(array $operatorMap, string $field): StringFilterModel|IntFilterModel|DateTimeFilterModel
    {
        return match ($this) {
            self::String => StringFilterModel::fromOperatorMap($operatorMap, $field),
            self::Int => IntFilterModel::fromOperatorMap($operatorMap, $field),
            self::DateTime => DateTimeFilterModel::fromOperatorMap($operatorMap, $field),
        };
    }
}
