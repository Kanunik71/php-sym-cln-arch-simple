<?php

declare(strict_types=1);

namespace App\Application\Policy\User;

use App\Application\Exception\User\InvalidCredentialsException;
use App\Application\Model\User\UserModel;
use App\Application\Port\PasswordHasherInterface;
use App\Application\Port\User\UserRepositoryInterface;
use App\Application\ValueObject\Common\Email;

final readonly class LoginUserPolicy
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function resolveAuthenticatedUserIo(string $email, string $password): UserModel
    {
        $user = $this->userRepository->findByEmail(new Email($email));

        if ($user === null || !$this->passwordHasher->verify($password, $user->passwordHash)) {
            throw InvalidCredentialsException::create();
        }

        return $user;
    }
}
