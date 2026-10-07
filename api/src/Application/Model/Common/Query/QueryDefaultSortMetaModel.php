<?php

declare(strict_types=1);

namespace App\Application\Model\Common\Query;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class QueryDefaultSortMetaModel implements ArrayableModelInterface
{
    public function __construct(
        public string $field,
        public string $direction,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            field: InputAssertUtils::requiredString($data['field'] ?? null, 'field'),
            direction: InputAssertUtils::requiredString($data['direction'] ?? null, 'direction'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'direction' => $this->direction,
        ];
    }
}
