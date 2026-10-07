<?php

declare(strict_types=1);

namespace App\Application\Model\Common\Filter;

use App\Application\Enum\Common\FilterOperatorEnum;
use App\Shared\Utils\Asserts\InputAssertUtils;
use InvalidArgumentException;

final readonly class IntFilterModel
{
    public function __construct(
        public FilterOperatorEnum $operator,
        public ?int $value = null,
        /** @var list<int>|null */
        public ?array $values = null,
    ) {
        $this->assertConsistent();
    }

    /** @param array<string, mixed> $operatorMap e.g. ['eq' => 10] */
    public static function fromOperatorMap(array $operatorMap, string $field): self
    {
        if (count($operatorMap) !== 1) {
            throw new InvalidArgumentException(sprintf('Filter "%s" must contain exactly one operator.', $field));
        }

        $operatorKey = array_key_first($operatorMap);
        $operator = FilterOperatorEnum::tryFrom((string) $operatorKey)
            ?? throw new InvalidArgumentException(sprintf('Filter "%s" has unknown operator "%s".', $field, (string) $operatorKey));

        if (!in_array($operator, FilterOperatorEnum::forInt(), true)) {
            throw new InvalidArgumentException(sprintf('Operator "%s" is not allowed for int filter "%s".', $operator->value, $field));
        }

        $raw = $operatorMap[$operatorKey];

        if (!$operator->needsValue()) {
            return new self($operator);
        }

        if ($operator === FilterOperatorEnum::In) {
            if (!is_array($raw) || $raw === []) {
                throw new InvalidArgumentException(sprintf('Filter "%s" operator "in" requires a non-empty list.', $field));
            }

            $values = [];
            foreach ($raw as $index => $item) {
                $values[] = InputAssertUtils::requiredInt($item, sprintf('%s.in[%s]', $field, (string) $index));
            }

            return new self($operator, values: $values);
        }

        return new self(
            $operator,
            value: InputAssertUtils::requiredInt($raw, sprintf('%s.%s', $field, $operator->value)),
        );
    }

    /** @return array<string, mixed> */
    public function toOperatorMap(): array
    {
        if (!$this->operator->needsValue()) {
            return [$this->operator->value => true];
        }

        if ($this->operator === FilterOperatorEnum::In) {
            return [$this->operator->value => $this->values];
        }

        return [$this->operator->value => $this->value];
    }

    private function assertConsistent(): void
    {
        if (!$this->operator->needsValue()) {
            return;
        }

        if ($this->operator === FilterOperatorEnum::In) {
            if ($this->values === null || $this->values === []) {
                throw new InvalidArgumentException('Int filter "in" requires values.');
            }

            return;
        }

        if ($this->value === null) {
            throw new InvalidArgumentException('Int filter requires a value.');
        }
    }
}
