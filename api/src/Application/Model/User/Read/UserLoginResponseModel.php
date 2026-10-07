<?php

declare(strict_types=1);

namespace App\Application\Model\User\Read;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class UserLoginResponseModel implements ArrayableModelInterface
{
    public function __construct(
        public string $token,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            token: InputAssertUtils::requiredString($data['token'] ?? null, 'token'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
        ];
    }
}
