<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Application\Model\TaskGroup\Read\TaskStatusCountsModel;
use App\Application\Model\TaskGroup\TaskGroupMembershipFactsModel;
use App\Application\Port\TaskGroup\TaskGroupRepositoryInterface;
use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Exception\TaskGroup\TaskGroupNotFoundException;
use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\TaskGroupEntity;
use App\Infrastructure\Persistence\Doctrine\Mapper\TaskGroupMapper;
use App\Infrastructure\Persistence\Doctrine\Query\TaskGroup\TaskGroupEntityFetcher;
use App\Infrastructure\Persistence\Doctrine\Utils\DoctrineUuidUtils;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTaskGroupRepository implements TaskGroupRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TaskGroupMapper $taskGroupMapper,
        private TaskGroupEntityFetcher $taskGroupEntityFetcher,
    ) {
    }

    public function save(TaskGroupModel $taskGroup): TaskGroupModel
    {
        $entity = $this->taskGroupEntityFetcher->findById($taskGroup->id);

        $entity = $this->taskGroupMapper->toEntity($taskGroup, $entity);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $this->findOrFail($taskGroup->id);
    }

    public function findById(string $id): ?TaskGroupModel
    {
        $entity = $this->taskGroupEntityFetcher->findById($id);

        return $entity !== null ? $this->taskGroupMapper->toModel($entity) : null;
    }

    public function findOrFail(string $id): TaskGroupModel
    {
        return $this->findById($id) ?? throw TaskGroupNotFoundException::withId($id);
    }

    public function listAll(): array
    {
        return $this->taskGroupMapper->toModelArray($this->taskGroupEntityFetcher->listAll());
    }

    public function listBatch(int $offset, int $limit): array
    {
        return $this->taskGroupMapper->toModelArray($this->taskGroupEntityFetcher->listBatch($offset, $limit));
    }

    public function listTaskStatusCountsByIds(array $taskGroupIds): array
    {
        if ($taskGroupIds === []) {
            return [];
        }

        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $joinTable = TaskGroupEntity::JOIN_TABLE_TASK;
        $groupCol = TaskGroupEntity::JOIN_COLUMN_TASK_GROUP_ID;
        $taskCol = TaskGroupEntity::JOIN_COLUMN_TASK_ID;
        $tasksTable = TaskEntity::TABLE;
        $statusCol = TaskEntity::COLUMN_STATUS;
        $idCol = TaskEntity::COLUMN_ID;
        [$placeholders, $params, $types] = DoctrineUuidUtils::expandInList($taskGroupIds, $platform, $groupCol);
        $inList = implode(', ', $placeholders);

        $sql = <<<SQL
            SELECT tgt.{$groupCol} AS {$groupCol},
                   t.{$statusCol} AS {$statusCol},
                   COUNT(t.{$idCol}) AS task_count
            FROM {$joinTable} tgt
            INNER JOIN {$tasksTable} t ON t.{$idCol} = tgt.{$taskCol}
            WHERE tgt.{$groupCol} IN ({$inList})
            GROUP BY tgt.{$groupCol}, t.{$statusCol}
            SQL;

        /** @var list<array{task_group_id: mixed, status: string, task_count: int|string}> $rows */
        $rows = $connection->fetchAllAssociative($sql, $params, $types);

        $countsById = [];

        foreach ($taskGroupIds as $taskGroupId) {
            $countsById[$taskGroupId] = TaskStatusCountsModel::empty();
        }

        foreach ($rows as $row) {
            $groupId = DoctrineUuidUtils::binaryToString($row[$groupCol], $platform);
            $status = TaskStatusEnum::from($row[$statusCol]);
            $countsById[$groupId] = $countsById[$groupId]->withCount($status, (int) $row['task_count']);
        }

        return $countsById;
    }

    public function listByTaskId(string $taskId): array
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $joinTable = TaskGroupEntity::JOIN_TABLE_TASK;
        $groupCol = TaskGroupEntity::JOIN_COLUMN_TASK_GROUP_ID;
        $taskCol = TaskGroupEntity::JOIN_COLUMN_TASK_ID;

        $sql = <<<SQL
            SELECT {$groupCol} FROM {$joinTable} WHERE {$taskCol} = :{$taskCol}
            SQL;

        /** @var list<string> $binaryGroupIds */
        $binaryGroupIds = $connection->fetchFirstColumn(
            $sql,
            [$taskCol => DoctrineUuidUtils::toDatabaseValue($taskId, $platform)],
            [$taskCol => DoctrineUuidUtils::bindingType()],
        );

        if ($binaryGroupIds === []) {
            return [];
        }

        $groupIds = DoctrineUuidUtils::binaryToStringArray($binaryGroupIds, $platform);

        $groups = $this->listByIds($groupIds);

        usort(
            $groups,
            static fn (TaskGroupModel $left, TaskGroupModel $right): int => $right->createdAt <=> $left->createdAt,
        );

        return $groups;
    }

    public function listByIds(array $ids): array
    {
        return $this->taskGroupMapper->toModelArray($this->taskGroupEntityFetcher->listByIds($ids));
    }

    public function containsTask(string $taskGroupId, string $taskId): bool
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $joinTable = TaskGroupEntity::JOIN_TABLE_TASK;
        $groupCol = TaskGroupEntity::JOIN_COLUMN_TASK_GROUP_ID;
        $taskCol = TaskGroupEntity::JOIN_COLUMN_TASK_ID;

        $sql = <<<SQL
            SELECT 1 FROM {$joinTable} WHERE {$groupCol} = :{$groupCol} AND {$taskCol} = :{$taskCol} LIMIT 1
            SQL;

        $exists = $connection->fetchOne(
            $sql,
            [
                $groupCol => DoctrineUuidUtils::toDatabaseValue($taskGroupId, $platform),
                $taskCol => DoctrineUuidUtils::toDatabaseValue($taskId, $platform),
            ],
            [
                $groupCol => DoctrineUuidUtils::bindingType(),
                $taskCol => DoctrineUuidUtils::bindingType(),
            ],
        );

        return $exists !== false && $exists !== null;
    }

    public function attachTask(string $taskGroupId, string $taskId): void
    {
        if ($this->containsTask($taskGroupId, $taskId)) {
            return;
        }

        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $joinTable = TaskGroupEntity::JOIN_TABLE_TASK;
        $groupCol = TaskGroupEntity::JOIN_COLUMN_TASK_GROUP_ID;
        $taskCol = TaskGroupEntity::JOIN_COLUMN_TASK_ID;

        $sql = <<<SQL
            INSERT IGNORE INTO {$joinTable} ({$groupCol}, {$taskCol}) VALUES (:{$groupCol}, :{$taskCol})
            SQL;

        $connection->executeStatement(
            $sql,
            [
                $groupCol => DoctrineUuidUtils::toDatabaseValue($taskGroupId, $platform),
                $taskCol => DoctrineUuidUtils::toDatabaseValue($taskId, $platform),
            ],
            [
                $groupCol => DoctrineUuidUtils::bindingType(),
                $taskCol => DoctrineUuidUtils::bindingType(),
            ],
        );
    }

    public function detachTask(string $taskGroupId, string $taskId): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $joinTable = TaskGroupEntity::JOIN_TABLE_TASK;
        $groupCol = TaskGroupEntity::JOIN_COLUMN_TASK_GROUP_ID;
        $taskCol = TaskGroupEntity::JOIN_COLUMN_TASK_ID;

        $sql = <<<SQL
            DELETE FROM {$joinTable} WHERE {$groupCol} = :{$groupCol} AND {$taskCol} = :{$taskCol}
            SQL;

        $connection->executeStatement(
            $sql,
            [
                $groupCol => DoctrineUuidUtils::toDatabaseValue($taskGroupId, $platform),
                $taskCol => DoctrineUuidUtils::toDatabaseValue($taskId, $platform),
            ],
            [
                $groupCol => DoctrineUuidUtils::bindingType(),
                $taskCol => DoctrineUuidUtils::bindingType(),
            ],
        );
    }

    public function listMembershipFactsByIds(array $taskGroupIds): array
    {
        if ($taskGroupIds === []) {
            return [];
        }

        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $nonTerminalStatuses = TaskStatusEnum::thatKeepAssetInProgress();

        $joinTable = TaskGroupEntity::JOIN_TABLE_TASK;
        $groupCol = TaskGroupEntity::JOIN_COLUMN_TASK_GROUP_ID;
        $taskCol = TaskGroupEntity::JOIN_COLUMN_TASK_ID;
        $tasksTable = TaskEntity::TABLE;
        $statusCol = TaskEntity::COLUMN_STATUS;
        $idCol = TaskEntity::COLUMN_ID;
        [$placeholders, $params, $types] = DoctrineUuidUtils::expandInList($taskGroupIds, $platform, $groupCol);

        $statusPlaceholders = [];
        foreach ($nonTerminalStatuses as $index => $status) {
            $key = $statusCol.$index;
            $statusPlaceholders[] = ':'.$key;
            $params[$key] = $status->value;
        }

        $statusInList = implode(', ', $statusPlaceholders);
        $inList = implode(', ', $placeholders);

        $sql = <<<SQL
            SELECT tgt.{$groupCol} AS {$groupCol},
                   COUNT(t.{$idCol}) AS task_count,
                   COALESCE(SUM(CASE WHEN t.{$statusCol} IN ({$statusInList}) THEN 1 ELSE 0 END), 0) AS has_non_terminal
            FROM {$joinTable} tgt
            INNER JOIN {$tasksTable} t ON t.{$idCol} = tgt.{$taskCol}
            WHERE tgt.{$groupCol} IN ({$inList})
            GROUP BY tgt.{$groupCol}
            SQL;

        /** @var list<array{task_group_id: mixed, task_count: int|string, has_non_terminal: int|string}> $rows */
        $rows = $connection->fetchAllAssociative($sql, $params, $types);

        $factsById = [];

        foreach ($taskGroupIds as $taskGroupId) {
            $factsById[$taskGroupId] = new TaskGroupMembershipFactsModel(taskCount: 0, hasNonTerminalTask: false);
        }

        foreach ($rows as $row) {
            $groupId = DoctrineUuidUtils::binaryToString($row[$groupCol], $platform);
            $factsById[$groupId] = new TaskGroupMembershipFactsModel(
                taskCount: (int) $row['task_count'],
                hasNonTerminalTask: (int) $row['has_non_terminal'] > 0,
            );
        }

        return $factsById;
    }

    public function delete(TaskGroupModel $taskGroup): void
    {
        $entity = $this->taskGroupEntityFetcher->findById($taskGroup->id);

        if ($entity === null) {
            return;
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}
