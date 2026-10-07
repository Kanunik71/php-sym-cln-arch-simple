<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Utils;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use LogicException;
use Symfony\Component\Uid\Uuid;

final class DoctrineUuidUtils
{
    public static function binaryToString(mixed $binaryUuid, AbstractPlatform $platform): string
    {
        $value = Type::getType('uuid')->convertToPHPValue($binaryUuid, $platform);

        if ($value instanceof Uuid) {
            return $value->toRfc4122();
        }

        if (is_string($value)) {
            return $value;
        }

        throw new LogicException('Expected UUID string from database value.');
    }

    /**
     * @param list<mixed> $binaryUuids
     *
     * @return list<string>
     */
    public static function binaryToStringArray(array $binaryUuids, AbstractPlatform $platform): array
    {
        return array_map(
            static fn (mixed $binaryUuid): string => self::binaryToString($binaryUuid, $platform),
            $binaryUuids,
        );
    }

    public static function toDatabaseValue(string $uuid, AbstractPlatform $platform): mixed
    {
        return Type::getType('uuid')->convertToDatabaseValue(Uuid::fromString($uuid), $platform);
    }

    public static function bindingType(): ParameterType
    {
        return Type::getType('uuid')->getBindingType();
    }

    /**
     * @param list<string> $ids
     *
     * @return array{0: list<string>, 1: array<string, mixed>, 2: array<string, ParameterType>}
     */
    public static function expandInList(array $ids, AbstractPlatform $platform, string $prefix): array
    {
        $placeholders = [];
        $params = [];
        $types = [];

        foreach ($ids as $index => $id) {
            $key = $prefix.$index;
            $placeholders[] = ':'.$key;
            $params[$key] = self::toDatabaseValue($id, $platform);
            $types[$key] = self::bindingType();
        }

        return [$placeholders, $params, $types];
    }
}
