<?php

declare(strict_types=1);

namespace App\Application\Mapper\User;

use App\Application\Model\User\Read\UserRegisterResponseModel;
use App\Application\Model\User\UserLocationModel;
use App\Application\Model\User\UserModel;

final class UserRegisterModelMapper
{
    public static function fromModel(UserModel $user): UserRegisterResponseModel
    {
        $city = $user->city;

        return new UserRegisterResponseModel(
            id: $user->id,
            fname: $user->fname,
            lname: $user->lname,
            email: $user->email,
            location: $city !== null ? new UserLocationModel(city: $city) : null,
            phone: $user->phone,
        );
    }
}
