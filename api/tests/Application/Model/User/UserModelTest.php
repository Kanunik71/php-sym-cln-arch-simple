<?php

declare(strict_types=1);

namespace App\Tests\Application\Model\User;

use App\Application\Model\User\UserModel;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UserModelTest extends TestCase
{
    public function testGetAllFileIdsReturnsEmptyListWhenAvatarMissing(): void
    {
        $user = UserModel::create(
            email: 'john@example.com',
            passwordHash: 'hash',
        );

        $this->assertSame([], $user->getAllFileIds());
    }

    public function testGetAllFileIdsReturnsAvatarFileId(): void
    {
        $now = new DateTimeImmutable();
        $user = new UserModel(
            id: 'user-1',
            fname: 'John',
            lname: 'Doe',
            email: 'john@example.com',
            passwordHash: 'hash',
            createdAt: $now,
            updatedAt: $now,
            avatarFileId: 'file-1',
        );

        $this->assertSame(['file-1'], $user->getAllFileIds());
    }

    public function testRejectsFirstNameExceedingMaxLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'First name must not exceed %d characters.',
            UserModel::FNAME_MAX_LENGTH,
        ));

        UserModel::create(
            email: 'john@example.com',
            passwordHash: 'hash',
            fname: str_repeat('a', UserModel::FNAME_MAX_LENGTH + 1),
        );
    }
}
