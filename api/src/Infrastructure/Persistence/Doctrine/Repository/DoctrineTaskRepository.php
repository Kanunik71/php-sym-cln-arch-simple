<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Exception\Task\TaskNotFoundException;
use App\Application\Model\Task\TaskModel;
use App\Application\Model\Task\Read\TaskViewModel;
use App\Application\Port\Task\TaskRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\TaskMapper;
use App\Infrastructure\Persistence\Doctrine\Query\Task\TaskEntityFetcher;
use App\Infrastructure\Persistence\Doctrine\Utils\DoctrineUuidUtils;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TaskMapper $taskMapper,
        private TaskEntityFetcher $taskEntityFetcher,
    ) {
    }

    public function save(TaskModel $task, ?array $users = null): TaskModel
    {
        $entity = $this->taskEntityFetcher->findViewById($task->id);

        $entity = $this->taskMapper->toEntity($task, $entity, $users);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $this->findOrFail($task->id);
    }

    public function findById(string $id): ?TaskModel
    {
        $entity = $this->taskEntityFetcher->findById($id);

        return $entity !== null ? $this->taskMapper->toModel($entity) : null;
    }

    public function findOrFail(string $id): TaskModel
    {
        return $this->findById($id) ?? throw TaskNotFoundException::withId($id);
    }

    public function findViewOrFail(string $id): TaskViewModel
    {
        $entity = $this->taskEntityFetcher->findViewById($id);

        if ($entity === null) {
            throw TaskNotFoundException::withId($id);
        }

        return $this->taskMapper->toViewModel($entity);
    }

    public function listByIds(array $ids): array
    {
        return $this->taskMapper->toModelArray($this->taskEntityFetcher->listByIds($ids));
    }

    public function listViewsByUserId(string $userId): array
    {
        return $this->taskMapper->toViewModelArray($this->taskEntityFetcher->listViewByUserId($userId));
    }

    public function listByUserId(string $userId): array
    {
        return $this->taskMapper->toModelArray($this->taskEntityFetcher->listByUserId($userId));
    }

    public function listUsers(string $taskId): array
    {
        $entity = $this->taskEntityFetcher->findViewById($taskId);

        if ($entity === null) {
            throw TaskNotFoundException::withId($taskId);
        }

        return $this->taskMapper->extractUsers($entity);
    }

    public function listStatusesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $statuses = [];

        $table = TaskEntity::TABLE;
        $statusCol = TaskEntity::COLUMN_STATUS;
        $idCol = TaskEntity::COLUMN_ID;

        $sql = <<<SQL
            SELECT {$statusCol} FROM {$table} WHERE {$idCol} = :{$idCol}
            SQL;

        foreach ($ids as $id) {
            $statusValue = $connection->fetchOne(
                $sql,
                [$idCol => DoctrineUuidUtils::toDatabaseValue($id, $platform)],
                [$idCol => DoctrineUuidUtils::bindingType()],
            );

            if (!is_string($statusValue)) {
                continue;
            }

            $statuses[$id] = TaskStatusEnum::from($statusValue);
        }

        return $statuses;
    }

    public function delete(TaskModel $task): void
    {
        $entity = $this->taskEntityFetcher->findViewById($task->id);

        if ($entity === null) {
            return;
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}
