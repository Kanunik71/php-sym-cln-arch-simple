<?php

declare(strict_types=1);

namespace App\Application\Model\User;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class UserLocationModel implements ArrayableModelInterface
{
    public function __construct(
        public string $city,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            city: InputAssertUtils::requiredString($data['city'] ?? null, 'location.city'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'city' => $this->city,
        ];
    }
}
