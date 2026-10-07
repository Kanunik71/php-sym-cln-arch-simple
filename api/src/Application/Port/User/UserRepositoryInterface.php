<?php

declare(strict_types=1);

namespace App\Application\Port\User;

use App\Application\Model\Common\PaginationModel;
use App\Application\Model\Common\SortModel;
use App\Application\Model\User\Read\UserPaginatedModel;
use App\Application\Model\User\UserFilterCriterionModel;
use App\Application\Model\User\UserModel;
use App\Application\ValueObject\Common\Email;

interface UserRepositoryInterface
{
    public function save(UserModel $user): UserModel;

    public function findById(string $id): ?UserModel;

    public function findOrFail(string $id): UserModel;

    public function findByEmail(Email $email): ?UserModel;

    public function existsByEmail(Email $email): bool;

    /** @return UserModel[] */
    public function listAll(): array;

    /**
     * Stable page for full-table batch scans (not API pagination).
     *
     * @return list<UserModel>
     */
    public function listBatch(int $offset, int $limit): array;

    /**
     * @param list<string> $ids
     *
     * @return list<UserModel>
     */
    public function listByIds(array $ids): array;

    /**
     * @param list<UserFilterCriterionModel> $criteria
     */
    public function search(
        array $criteria,
        PaginationModel $pagination,
        SortModel $sort,
    ): UserPaginatedModel;

    public function delete(UserModel $user): void;
}
