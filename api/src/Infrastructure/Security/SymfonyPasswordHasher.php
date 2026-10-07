<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Port\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function hash(string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword(new SecurityUser('00000000-0000-7000-8000-000000000000', '', ''), $plainPassword);
    }

    public function verify(string $plainPassword, string $hashedPassword): bool
    {
        return $this->passwordHasher->isPasswordValid(
            new SecurityUser('00000000-0000-7000-8000-000000000000', '', $hashedPassword),
            $plainPassword,
        );
    }
}
