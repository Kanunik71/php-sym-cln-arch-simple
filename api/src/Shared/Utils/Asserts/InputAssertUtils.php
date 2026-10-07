<?php

declare(strict_types=1);

namespace App\Shared\Utils\Asserts;

use App\Shared\Utils\ArrayUtils;
use App\Shared\Utils\EnumUtils;
use BackedEnum;
use InvalidArgumentException;

final class InputAssertUtils
{
    public static function requiredString(mixed $value, string $field): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(sprintf('Field "%s" is required.', $field));
        }

        return trim($value);
    }

    public static function optionalString(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be a string.', $field));
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    public static function optionalNonEmptyString(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(sprintf('Field "%s" must be a non-empty string.', $field));
        }

        return trim($value);
    }

    public static function optionalInt(mixed $value, string $field): ?int
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be a number.', $field));
        }

        return (int) $value;
    }

    public static function requiredInt(mixed $value, string $field): int
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be a number.', $field));
        }

        return (int) $value;
    }

    public static function requiredFloat(mixed $value, string $field): float
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be a number.', $field));
        }

        return (float) $value;
    }

    public static function optionalFloat(mixed $value, string $field): ?float
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be a number.', $field));
        }

        return (float) $value;
    }

    public static function stringOrEmpty(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    public static function requiredBool(mixed $value, string $field): bool
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be a boolean.', $field));
        }

        return $value;
    }

    /**
     * @param array<mixed, mixed> $value
     *
     * @return array<string, mixed>
     */
    public static function stringKeyedArray(array $value, string $field): array
    {
        $result = [];

        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new InvalidArgumentException(sprintf('Field "%s" keys must be strings.', $field));
            }

            $result[$key] = $item;
        }

        return $result;
    }

    /**
     * @template T of BackedEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return T
     */
    public static function requiredEnum(mixed $value, string $field, string $enumClass): BackedEnum
    {
        $string = self::requiredString($value, $field);
        $enum = $enumClass::tryFrom($string);

        if ($enum === null) {
            $allowed = implode(', ', array_map(strval(...), EnumUtils::caseValues($enumClass)));

            throw new InvalidArgumentException(sprintf('Field "%s" has invalid value. Allowed: %s.', $field, $allowed));
        }

        return $enum;
    }

    /**
     * @template T of BackedEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return T|null
     */
    public static function optionalEnum(mixed $value, string $field, string $enumClass): ?BackedEnum
    {
        $string = self::optionalString($value, $field);

        if ($string === null) {
            return null;
        }

        return self::requiredEnum($string, $field, $enumClass);
    }

    public static function optionalUuid(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        return self::requiredUuid($value, $field);
    }

    public static function requiredUuid(mixed $value, string $field): string
    {
        if (!is_string($value) || preg_match('/^[0-9a-fA-F-]{36}$/', $value) !== 1) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be a valid UUID.', $field));
        }

        return $value;
    }

    /** @return list<string>|null */
    public static function optionalUuidList(mixed $value, string $field): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be an array.', $field));
        }

        return ArrayUtils::valuesMap(
            $value,
            static fn (mixed $item): string => self::requiredUuid($item, $field.'[]'),
        );
    }

    /** @return list<string> */
    public static function stringList(mixed $value, string $field): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be an array.', $field));
        }

        return ArrayUtils::valuesMap(
            $value,
            static fn (mixed $item): string => self::requiredString($item, $field.'[]'),
        );
    }

    /**
     * @template T of BackedEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return list<T>
     */
    public static function enumList(mixed $value, string $field, string $enumClass): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be an array.', $field));
        }

        return ArrayUtils::valuesMap(
            $value,
            static fn (mixed $item): BackedEnum => self::requiredEnum($item, $field.'[]', $enumClass),
        );
    }

    /** @return array<string, mixed>|null */
    public static function optionalArray(mixed $value, string $field): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be an object.', $field));
        }

        return self::stringKeyedArray($value, $field);
    }

    /** @return array<string, mixed> */
    public static function requiredArray(mixed $value, string $field): array
    {
        if ($value === null) {
            throw new InvalidArgumentException(sprintf('Field "%s" is required.', $field));
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be an object.', $field));
        }

        return self::stringKeyedArray($value, $field);
    }

    /**
     * @template T
     *
     * @param callable(array<string, mixed>): T $fromArray
     *
     * @return T|null
     */
    public static function optionalNestedModel(mixed $value, string $field, callable $fromArray): mixed
    {
        $array = self::optionalArray($value, $field);

        return $array !== null ? $fromArray($array) : null;
    }

    /**
     * @template T
     *
     * @param callable(array<string, mixed>): T $fromArray
     *
     * @return T
     */
    public static function requiredNestedModel(mixed $value, string $field, callable $fromArray): mixed
    {
        return $fromArray(self::requiredArray($value, $field));
    }

    /**
     * @template T
     *
     * @param callable(array<string, mixed>): T $fromArray
     *
     * @return list<T>
     */
    public static function nestedModelList(mixed $value, string $field, callable $fromArray): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" must be an array.', $field));
        }

        return ArrayUtils::valuesMap(
            $value,
            static function (mixed $item) use ($field, $fromArray): mixed {
                if (!is_array($item)) {
                    throw new InvalidArgumentException(sprintf('Field "%s[]" must be an object.', $field));
                }

                return $fromArray(self::stringKeyedArray($item, $field.'[]'));
            },
        );
    }
}
