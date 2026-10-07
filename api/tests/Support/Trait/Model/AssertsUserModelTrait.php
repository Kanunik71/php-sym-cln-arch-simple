<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait\Model;

use App\Application\Model\User\UserModel;
use App\Application\Model\User\Read\UserViewModel;
use App\Shared\Utils\DateUtils;

trait AssertsUserModelTrait
{
    private function assertUserModelMatches(UserModel $user, UserViewModel $model): void
    {
        $this->assertSame($user->id, $model->id);
        $this->assertSame($user->fname, $model->fname);
        $this->assertSame($user->lname, $model->lname);
        $this->assertSame($user->email, $model->email);
        $this->assertSame(DateUtils::toAtom($user->createdAt), $model->createdAt);
        $this->assertSame($user->city, $model->location?->city);
        $this->assertSame($user->phone, $model->phone);

        $avatarFileId = $user->avatarFileId;

        if ($avatarFileId === null) {
            $this->assertNull($model->avatar);
        } else {
            $this->assertNotNull($model->avatar);
            $this->assertSame($avatarFileId, $model->avatar->id);
            $this->assertStringContainsString($avatarFileId, $model->avatar->url);
        }
    }
}
