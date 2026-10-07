<?php

declare(strict_types=1);

namespace App\Application\Model\Common\Query;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class QueryFilterFieldMetaModel implements ArrayableModelInterface
{
    /**
     * @param list<string> $operators
     */
    public function __construct(
        public string $field,
        public string $type,
        public array $operators,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            field: InputAssertUtils::requiredString($data['field'] ?? null, 'field'),
            type: InputAssertUtils::requiredString($data['type'] ?? null, 'type'),
            operators: InputAssertUtils::stringList($data['operators'] ?? null, 'operators'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'type' => $this->type,
            'operators' => $this->operators,
        ];
    }
}
