<?php

declare(strict_types=1);

namespace App\Application\Model\User\Read;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Model\Common\FileItemModel;
use App\Application\Model\User\UserLocationModel;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class UserViewModel implements ArrayableModelInterface
{
    public function __construct(
        public string $id,
        public ?string $fname,
        public ?string $lname,
        public string $email,
        public string $createdAt,
        public ?UserLocationModel $location = null,
        public ?FileItemModel $avatar = null,
        public ?string $phone = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: InputAssertUtils::requiredString($data['id'] ?? null, 'id'),
            fname: InputAssertUtils::optionalString($data['fname'] ?? null, 'fname'),
            lname: InputAssertUtils::optionalString($data['lname'] ?? null, 'lname'),
            email: InputAssertUtils::requiredString($data['email'] ?? null, 'email'),
            createdAt: InputAssertUtils::requiredString($data['createdAt'] ?? null, 'createdAt'),
            location: InputAssertUtils::optionalNestedModel(
                $data['location'] ?? null,
                'location',
                UserLocationModel::fromArray(...),
            ),
            avatar: InputAssertUtils::optionalNestedModel(
                $data['avatar'] ?? null,
                'avatar',
                FileItemModel::fromArray(...),
            ),
            phone: InputAssertUtils::optionalString($data['phone'] ?? null, 'phone'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'fname' => $this->fname,
            'lname' => $this->lname,
            'email' => $this->email,
            'createdAt' => $this->createdAt,
            'location' => $this->location?->toArray(),
            'avatar' => $this->avatar?->toArray(),
            'phone' => $this->phone,
        ];
    }
}
