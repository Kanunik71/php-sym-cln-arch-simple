<?php

declare(strict_types=1);

namespace App\Application\Model\User\Action;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Model\User\UserLocationModel;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class UserUpdateModel implements ArrayableModelInterface
{
    public function __construct(
        public ?string $fname,
        public ?string $lname,
        public string $email,
        public ?string $password = null,
        public ?UserLocationModel $location = null,
        public ?string $avatarFileId = null,
        public ?string $phone = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            fname: InputAssertUtils::optionalString($data['fname'] ?? null, 'fname'),
            lname: InputAssertUtils::optionalString($data['lname'] ?? null, 'lname'),
            email: InputAssertUtils::requiredString($data['email'] ?? null, 'email'),
            password: InputAssertUtils::optionalNonEmptyString($data['password'] ?? null, 'password'),
            location: InputAssertUtils::optionalNestedModel(
                $data['location'] ?? null,
                'location',
                UserLocationModel::fromArray(...),
            ),
            avatarFileId: InputAssertUtils::optionalUuid($data['avatarFileId'] ?? null, 'avatarFileId'),
            phone: InputAssertUtils::optionalString($data['phone'] ?? null, 'phone'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'fname' => $this->fname,
            'lname' => $this->lname,
            'email' => $this->email,
            'password' => $this->password,
            'location' => $this->location?->toArray(),
            'avatarFileId' => $this->avatarFileId,
            'phone' => $this->phone,
        ];
    }
}
