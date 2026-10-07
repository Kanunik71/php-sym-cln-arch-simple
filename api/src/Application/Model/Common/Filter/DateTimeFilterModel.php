<?php

declare(strict_types=1);

namespace App\Application\Model\Common\Filter;

use App\Application\Enum\Common\FilterOperatorEnum;
use App\Shared\Utils\Asserts\InputAssertUtils;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;

final readonly class DateTimeFilterModel
{
    public function __construct(
        public FilterOperatorEnum $operator,
        public ?DateTimeImmutable $value = null,
    ) {
        $this->assertConsistent();
    }

    /** @param array<string, mixed> $operatorMap */
    public static function fromOperatorMap(array $operatorMap, string $field): self
    {
        if (count($operatorMap) !== 1) {
            throw new InvalidArgumentException(sprintf('Filter "%s" must contain exactly one operator.', $field));
        }

        $operatorKey = array_key_first($operatorMap);
        $operator = FilterOperatorEnum::tryFrom((string) $operatorKey)
            ?? throw new InvalidArgumentException(sprintf('Filter "%s" has unknown operator "%s".', $field, (string) $operatorKey));

        if (!in_array($operator, FilterOperatorEnum::forDateTime(), true)) {
            throw new InvalidArgumentException(sprintf('Operator "%s" is not allowed for datetime filter "%s".', $operator->value, $field));
        }

        if (!$operator->needsValue()) {
            return new self($operator);
        }

        $raw = $operatorMap[$operatorKey];
        $stringValue = InputAssertUtils::requiredString($raw, sprintf('%s.%s', $field, $operator->value));

        try {
            $date = new DateTimeImmutable($stringValue);
        } catch (Exception) {
            throw new InvalidArgumentException(sprintf('Filter "%s" must be a valid datetime.', $field));
        }

        return new self($operator, $date);
    }

    /** @return array<string, mixed> */
    public function toOperatorMap(): array
    {
        if (!$this->operator->needsValue()) {
            return [$this->operator->value => true];
        }

        return [$this->operator->value => $this->value?->format(DATE_ATOM)];
    }

    private function assertConsistent(): void
    {
        if ($this->operator->needsValue() && $this->value === null) {
            throw new InvalidArgumentException('Datetime filter requires a value.');
        }
    }
}
