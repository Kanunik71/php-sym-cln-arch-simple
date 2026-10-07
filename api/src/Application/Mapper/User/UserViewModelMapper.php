<?php

declare(strict_types=1);

namespace App\Application\Mapper\User;

use App\Application\Mapper\Common\FileItemModelMapper;
use App\Application\Model\User\UserLocationModel;
use App\Application\Model\User\UserModel;
use App\Application\Model\User\Read\UserViewModel;
use App\Application\Port\File\FileDownloadUrlProviderInterface;
use App\Shared\Utils\ArrayUtils;
use App\Shared\Utils\DateUtils;

final class UserViewModelMapper
{
    public static function fromModel(UserModel $user, FileDownloadUrlProviderInterface $urlProvider): UserViewModel
    {
        $city = $user->city;

        return new UserViewModel(
            id: $user->id,
            fname: $user->fname,
            lname: $user->lname,
            email: $user->email,
            createdAt: DateUtils::toAtom($user->createdAt),
            location: $city !== null ? new UserLocationModel(city: $city) : null,
            avatar: FileItemModelMapper::fromFileId($user->avatarFileId, $urlProvider),
            phone: $user->phone,
        );
    }

    /**
     * @param array<UserModel> $users
     *
     * @return list<UserViewModel>
     */
    public static function fromModelList(array $users, FileDownloadUrlProviderInterface $urlProvider): array
    {
        return ArrayUtils::valuesMap(
            $users,
            fn (UserModel $user): UserViewModel => self::fromModel($user, $urlProvider),
        );
    }
}
