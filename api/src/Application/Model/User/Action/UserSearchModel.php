<?php

declare(strict_types=1);

namespace App\Application\Model\User\Action;

use App\Application\Model\Common\ArrayableModelInterface;
use App\Application\Model\Common\PaginationModel;
use App\Application\Model\Common\SortModel;
use App\Application\Model\User\UserFilterCriterionModel;

final readonly class UserSearchModel implements ArrayableModelInterface
{
    /**
     * @param list<UserFilterCriterionModel> $criteria
     */
    public function __construct(
        public PaginationModel $pagination,
        public array $criteria,
        public ?SortModel $sort = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            pagination: PaginationModel::fromArray($data),
            criteria: UserFilterCriterionModel::fromArrayList($data),
            sort: isset($data['sort'])
                ? SortModel::fromArray($data)
                : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_merge(
            $this->pagination->toArray(),
            UserFilterCriterionModel::toArrayList($this->criteria),
            $this->sort?->toArray() ?? [],
        );
    }
}
