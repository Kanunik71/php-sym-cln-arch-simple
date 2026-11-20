<?php

declare(strict_types=1);

namespace App\Tests\Application\Model\User;

use App\Application\Model\User\UserModel;
use App\Application\ValueObject\Common\Email;
use App\Application\ValueObject\Common\Phone;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UserModelTest extends TestCase
{
    public function testGetAllFileIdsReturnsEmptyListWhenAvatarMissing(): void
    {
        $user = UserModel::create(
            email: new Email('John@Example.com'),
            passwordHash: 'hash',
        );

        $this->assertSame([], $user->getAllFileIds());
        $this->assertSame('john@example.com', $user->email->toString());
    }

    public function testGetAllFileIdsReturnsAvatarFileId(): void
    {
        $now = new DateTimeImmutable();
        $user = new UserModel(
            id: 'user-1',
            fname: 'John',
            lname: 'Doe',
            email: new Email('john@example.com'),
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
            email: new Email('john@example.com'),
            passwordHash: 'hash',
            fname: str_repeat('a', UserModel::FNAME_MAX_LENGTH + 1),
        );
    }

    public function testStoresNormalizedPhone(): void
    {
        $user = UserModel::create(
            email: new Email('john@example.com'),
            passwordHash: 'hash',
            phone: new Phone('+7 900 123-45-67'),
        );

        $this->assertSame('+79001234567', $user->phone?->toString());
    }
}
