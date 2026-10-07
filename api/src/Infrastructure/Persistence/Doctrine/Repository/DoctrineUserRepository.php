<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Application\Enum\User\UserSortFieldEnum;
use App\Application\Model\Common\PaginationModel;
use App\Application\Model\Common\SortModel;
use App\Application\Model\User\Read\UserPaginatedModel;
use App\Application\Model\User\UserFilterCriterionModel;
use App\Application\Port\User\UserRepositoryInterface;
use App\Application\ValueObject\Common\Email;
use App\Application\Exception\User\UserNotFoundException;
use App\Application\Model\User\UserModel;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\UserMapper;
use App\Infrastructure\Persistence\Doctrine\Query\Common\DoctrinePaginationApplier;
use App\Infrastructure\Persistence\Doctrine\Query\User\UserEntityFetcher;
use App\Infrastructure\Persistence\Doctrine\Query\User\UserListCriteriaApplier;
use App\Infrastructure\Persistence\Doctrine\Query\User\UserSortFieldDoctrineMap;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserMapper $userMapper,
        private UserEntityFetcher $userEntityFetcher,
        private UserListCriteriaApplier $userListCriteriaApplier,
        private DoctrinePaginationApplier $paginationApplier,
    ) {
    }

    public function save(UserModel $user): UserModel
    {
        $entity = $this->userEntityFetcher->findById($user->id);

        $entity = $this->userMapper->toEntity($user, $entity);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $this->findOrFail($user->id);
    }

    public function findById(string $id): ?UserModel
    {
        $entity = $this->userEntityFetcher->findById($id);

        return $entity !== null ? $this->userMapper->toModel($entity) : null;
    }

    public function findOrFail(string $id): UserModel
    {
        return $this->findById($id) ?? throw UserNotFoundException::withId($id);
    }

    public function findByEmail(Email $email): ?UserModel
    {
        $entity = $this->userEntityFetcher->findByEmail($email->toString());

        return $entity !== null ? $this->userMapper->toModel($entity) : null;
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function listAll(): array
    {
        return $this->userMapper->toModelArray($this->userEntityFetcher->listAll());
    }

    public function listBatch(int $offset, int $limit): array
    {
        return $this->userMapper->toModelArray($this->userEntityFetcher->listBatch($offset, $limit));
    }

    public function listByIds(array $ids): array
    {
        return $this->userMapper->toModelArray($this->userEntityFetcher->listByIds($ids));
    }

    /**
     * @param list<UserFilterCriterionModel> $criteria
     */
    public function search(
        array $criteria,
        PaginationModel $pagination,
        SortModel $sort,
    ): UserPaginatedModel {
        $alias = $this->userEntityFetcher->rootAlias();
        $qb = $this->userEntityFetcher->createListQueryBuilder();

        $this->userListCriteriaApplier->apply($qb, $criteria, $alias);

        $countQb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT u.'.UserEntity::FIELD_ID.')')
            ->from(UserEntity::class, 'u');
        $this->userListCriteriaApplier->apply($countQb, $criteria, 'u');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $fieldEnum = UserSortFieldEnum::from($sort->field);

        $this->paginationApplier->applySort(
            $qb,
            UserSortFieldDoctrineMap::path($fieldEnum, $alias),
            $sort->direction,
        );
        $this->paginationApplier->applyLimit($qb, $pagination);

        /** @var list<UserEntity> $entities */
        $entities = $qb->getQuery()->getResult();

        return new UserPaginatedModel(
            items: $this->userMapper->toModelArray($entities),
            total: $total,
        );
    }

    public function delete(UserModel $user): void
    {
        $entity = $this->userEntityFetcher->findById($user->id);

        if ($entity === null) {
            return;
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}
