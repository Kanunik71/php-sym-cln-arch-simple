<?php

declare(strict_types=1);

namespace App\Application\Model\Common\Query;

use App\Application\Enum\Common\FilterFieldTypeEnum;
use App\Application\Enum\Common\FilterOperatorEnum;

/**
 * Contract for search filter field enums exposed via SearchQueryMetaService.
 */
interface FilterFieldMetaInterface
{
    public function type(): FilterFieldTypeEnum;

    /** @return list<FilterOperatorEnum> */
    public function operators(): array;
}
