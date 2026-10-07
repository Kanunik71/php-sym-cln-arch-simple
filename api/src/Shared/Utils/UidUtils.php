<?php

declare(strict_types=1);

namespace App\Shared\Utils;

use Symfony\Component\Uid\Uuid;

final class UidUtils
{
    /** RFC 4122 nil UUID — guaranteed to not exist in the database. */
    public const NIL = '00000000-0000-0000-0000-000000000000';

    public static function generate(): Uuid
    {
        return Uuid::v7();
    }

    public static function generateString(): string
    {
        return self::randomUid();
    }

    public static function randomUid(): string
    {
        return self::toString(self::generate());
    }

    public static function nil(): string
    {
        return self::NIL;
    }

    public static function toString(Uuid $uuid): string
    {
        return $uuid->toRfc4122();
    }
}
