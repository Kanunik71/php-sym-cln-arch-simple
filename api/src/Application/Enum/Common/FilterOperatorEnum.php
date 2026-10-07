<?php

declare(strict_types=1);

namespace App\Application\Enum\Common;

enum FilterOperatorEnum: string
{
    case Eq = 'eq';
    case Ne = 'ne';
    case Gt = 'gt';
    case Gte = 'gte';
    case Lt = 'lt';
    case Lte = 'lte';
    case Like = 'like';
    case In = 'in';
    case IsNull = 'isNull';
    case IsNotNull = 'isNotNull';

    /** @return list<self> */
    public static function forString(): array
    {
        return [self::Eq, self::Ne, self::Like, self::In, self::IsNull, self::IsNotNull];
    }

    /** @return list<self> */
    public static function forInt(): array
    {
        return [self::Eq, self::Ne, self::Gt, self::Gte, self::Lt, self::Lte, self::In, self::IsNull, self::IsNotNull];
    }

    /** @return list<self> */
    public static function forDateTime(): array
    {
        return [self::Eq, self::Ne, self::Gt, self::Gte, self::Lt, self::Lte, self::IsNull, self::IsNotNull];
    }

    public function needsValue(): bool
    {
        return $this !== self::IsNull && $this !== self::IsNotNull;
    }
}
