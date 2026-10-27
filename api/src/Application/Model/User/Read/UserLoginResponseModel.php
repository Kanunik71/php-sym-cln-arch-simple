<?php

declare(strict_types=1);

namespace App\Application\Model\User\Read;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class UserLoginResponseModel implements ArrayableModelInterface
{
    public function __construct(
        public string $token,
        public string $refreshToken,
        public string $tokenType = 'Bearer',
        public int $expiresIn = 900,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            token: InputAssertUtils::requiredString($data['token'] ?? null, 'token'),
            refreshToken: InputAssertUtils::requiredString($data['refreshToken'] ?? null, 'refreshToken'),
            tokenType: InputAssertUtils::optionalString($data['tokenType'] ?? null, 'tokenType') ?? 'Bearer',
            expiresIn: isset($data['expiresIn'])
                ? InputAssertUtils::requiredInt($data['expiresIn'], 'expiresIn')
                : 900,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'refreshToken' => $this->refreshToken,
            'tokenType' => $this->tokenType,
            'expiresIn' => $this->expiresIn,
        ];
    }
}
