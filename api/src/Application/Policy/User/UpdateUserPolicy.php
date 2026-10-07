<?php

declare(strict_types=1);

namespace App\Application\Policy\User;

use App\Application\Exception\User\UserAlreadyExistsException;
use App\Application\Model\User\UserModel;
use App\Application\Port\User\UserRepositoryInterface;
use App\Application\ValueObject\Common\Email;

final readonly class UpdateUserPolicy
{
    public function __construct(
        private UserAccessPolicy $userAccessPolicy,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function assertCanUpdate(string $userId, string $currentUserId): void
    {
        $this->userAccessPolicy->assertSelfAccess($userId, $currentUserId);
    }

    public function assertEmailAvailableIo(Email $email, UserModel $user): void
    {
        if ($email->toString() !== $user->email && $this->userRepository->existsByEmail($email)) {
            throw UserAlreadyExistsException::withEmail($email->toString());
        }
    }
}
