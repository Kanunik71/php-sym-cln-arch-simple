<?php

declare(strict_types=1);

namespace App\Application\Model\Common\Query;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class QueryPaginationMetaModel implements ArrayableModelInterface
{
    public function __construct(
        public int $defaultPage,
        public int $defaultPerPage,
        public int $maxPerPage,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            defaultPage: InputAssertUtils::requiredInt($data['defaultPage'] ?? 1, 'defaultPage'),
            defaultPerPage: InputAssertUtils::requiredInt($data['defaultPerPage'] ?? 20, 'defaultPerPage'),
            maxPerPage: InputAssertUtils::requiredInt($data['maxPerPage'] ?? 100, 'maxPerPage'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'defaultPage' => $this->defaultPage,
            'defaultPerPage' => $this->defaultPerPage,
            'maxPerPage' => $this->maxPerPage,
        ];
    }
}
