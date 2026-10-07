<?php

declare(strict_types=1);

namespace App\Application\Model\User\Action;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class UserLoginModel implements ArrayableModelInterface
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            email: InputAssertUtils::requiredString($data['email'] ?? null, 'email'),
            password: InputAssertUtils::requiredString($data['password'] ?? null, 'password'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
