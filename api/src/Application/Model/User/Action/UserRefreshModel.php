<?php

declare(strict_types=1);

namespace App\Application\Model\User\Action;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class UserRefreshModel implements ArrayableModelInterface
{
    public function __construct(
        public string $refreshToken,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            refreshToken: InputAssertUtils::requiredString($data['refreshToken'] ?? null, 'refreshToken'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'refreshToken' => $this->refreshToken,
        ];
    }
}
