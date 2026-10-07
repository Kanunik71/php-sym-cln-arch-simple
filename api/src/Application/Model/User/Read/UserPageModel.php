<?php

declare(strict_types=1);

namespace App\Application\Model\User\Read;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Shared\Utils\Asserts\InputAssertUtils;

final readonly class UserPageModel implements ArrayableModelInterface
{
    /**
     * @param list<UserViewModel> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            items: InputAssertUtils::nestedModelList(
                $data['items'] ?? null,
                'items',
                UserViewModel::fromArray(...),
            ),
            total: InputAssertUtils::requiredInt($data['total'] ?? 0, 'total'),
            page: InputAssertUtils::requiredInt($data['page'] ?? 1, 'page'),
            perPage: InputAssertUtils::requiredInt($data['perPage'] ?? 20, 'perPage'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'items' => array_map(
                static fn (UserViewModel $item): array => $item->toArray(),
                $this->items,
            ),
            'total' => $this->total,
            'page' => $this->page,
            'perPage' => $this->perPage,
        ];
    }
}
