<?php

declare(strict_types=1);

namespace App\Application\Model\User\Action;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Model\User\UserLocationModel;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class UserRegisterModel implements ArrayableModelInterface
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $fname = null,
        public ?string $lname = null,
        public ?UserLocationModel $location = null,
        public ?string $phone = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            email: InputAssertUtils::requiredString($data['email'] ?? null, 'email'),
            password: InputAssertUtils::requiredString($data['password'] ?? null, 'password'),
            fname: InputAssertUtils::optionalString($data['fname'] ?? null, 'fname'),
            lname: InputAssertUtils::optionalString($data['lname'] ?? null, 'lname'),
            location: InputAssertUtils::optionalNestedModel(
                $data['location'] ?? null,
                'location',
                UserLocationModel::fromArray(...),
            ),
            phone: InputAssertUtils::optionalString($data['phone'] ?? null, 'phone'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'fname' => $this->fname,
            'lname' => $this->lname,
            'location' => $this->location?->toArray(),
            'phone' => $this->phone,
        ];
    }
}
