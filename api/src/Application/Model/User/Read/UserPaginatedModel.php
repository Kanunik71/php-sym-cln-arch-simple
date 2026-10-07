<?php

declare(strict_types=1);

namespace App\Application\Model\User\Read;

use App\Application\Model\User\UserModel;

final readonly class UserPaginatedModel
{
    /**
     * @param list<UserModel> $items
     */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }
}
