<?php

declare(strict_types=1);

namespace App\Application\Model\User\Read;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Model\User\UserLocationModel;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class UserRegisterResponseModel implements ArrayableModelInterface
{
    public function __construct(
        public string $id,
        public ?string $fname,
        public ?string $lname,
        public string $email,
        public ?UserLocationModel $location = null,
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
            'id' => $this->id,
            'fname' => $this->fname,
            'lname' => $this->lname,
            'email' => $this->email,
            'location' => $this->location?->toArray(),
            'phone' => $this->phone,
        ];
    }
}
