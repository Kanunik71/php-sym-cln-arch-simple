<?php

declare(strict_types=1);

namespace App\Application\Model\Common;

use App\Application\Enum\Common\SortDirectionEnum;
use App\Shared\Utils\Asserts\InputAssertUtils;
use InvalidArgumentException;

final readonly class SortModel implements ArrayableModelInterface
{
    public function __construct(
        public string $field,
        public SortDirectionEnum $direction,
    ) {
    }

    /**
     * Supports `sort=createdAt` or `sort=-createdAt` (leading `-` = desc).
     * Call only when `sort` is present in wire data; default — use-case (`Search*Query`).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $raw = InputAssertUtils::requiredString($data['sort'] ?? null, 'sort');

        $direction = SortDirectionEnum::Asc;
        $field = $raw;

        if (str_starts_with($raw, '-')) {
            $direction = SortDirectionEnum::Desc;
            $field = substr($raw, 1);
        }

        if ($field === '') {
            throw new InvalidArgumentException('Field "sort" must be a field name (optional "-" prefix for desc).');
        }

        return new self($field, $direction);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $prefix = $this->direction === SortDirectionEnum::Desc ? '-' : '';

        return [
            'sort' => $prefix.$this->field,
        ];
    }
}
