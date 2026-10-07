<?php

declare(strict_types=1);

namespace App\Application\Policy\User;

use App\Application\Port\User\UserRepositoryInterface;
use App\Application\ValueObject\Common\Email;
use App\Application\Exception\User\UserAlreadyExistsException;

final readonly class RegisterUserPolicy
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function assertEmailAvailableIo(Email $email): void
    {
        if ($this->userRepository->existsByEmail($email)) {
            throw UserAlreadyExistsException::withEmail($email->toString());
        }
    }
}
