<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\File\Action;

use App\Application\Service\User\Action\UserUpdateService;
use App\Application\Enum\File\FileStatusEnum;
use App\Application\Model\User\Action\UserUpdateModel;
use App\Tests\DatabaseTestCase;

final class FileUploadTmpServiceTest extends DatabaseTestCase
{
    public function testUploadCreatesTemporaryFileInStorage(): void
    {
        $user = $this->bed->seedDefaultUser();
        $file = $this->bed->uploadTmpImage($user->getId());

        $this->assertSame(FileStatusEnum::Temporary, $file->status);
        $this->assertSame('tmp/'.$file->id, $file->storageKey);
    }

    public function testUpdateUserPromotesAvatarFile(): void
    {
        $user = $this->bed->seedDefaultUser();
        $file = $this->bed->uploadTmpImage($user->getId());

        $updateUser = $this->bed->get(UserUpdateService::class);
        $result = $updateUser->execute(
            new UserUpdateModel(
                fname: $user->getFname(),
                lname: $user->getLname(),
                email: $user->getEmail()->toString(),
                avatarFileId: $file->id,
            ),
            userId: $user->getId(),
            currentUserId: $user->getId(),
        );

        $this->assertNotNull($result->avatar);
        $this->assertSame($file->id, $result->avatar->id);

        $this->bed->clear();
        $promoted = $this->bed->findFileById($file->id);
        $this->assertNotNull($promoted);
        $this->assertSame(FileStatusEnum::Permanent, $promoted->status);
    }
}
