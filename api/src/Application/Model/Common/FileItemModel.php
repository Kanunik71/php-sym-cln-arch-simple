<?php

declare(strict_types=1);

namespace App\Application\Model\Common;

use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class FileItemModel implements ArrayableModelInterface
{
    public function __construct(
        public string $id,
        public string $url,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: InputAssertUtils::requiredString($data['id'] ?? null, 'id'),
            url: InputAssertUtils::requiredString($data['url'] ?? null, 'url'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
        ];
    }
}
