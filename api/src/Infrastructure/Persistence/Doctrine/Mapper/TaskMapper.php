<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Application\Mapper\Task\TaskLightModelMapper;
use App\Application\Model\Task\TaskModel;
use App\Application\Model\Task\TaskUserModel;
use App\Application\Model\Task\Read\TaskLightModel;
use App\Application\Model\Task\Read\TaskViewModel;
use App\Infrastructure\Persistence\Doctrine\Entity\TaskEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\TaskUserEntity;
use App\Infrastructure\Persistence\Doctrine\Entity\UserEntity;
use App\Infrastructure\Persistence\Doctrine\EntityReferenceAdapter;
use App\Infrastructure\Persistence\Doctrine\Utils\DoctrineCollectionAssertUtils;
use App\Shared\Utils\DateUtils;
use App\Shared\Utils\UidUtils;
use Symfony\Component\Uid\Uuid;

final readonly class TaskMapper
{
    public function __construct(
        private EntityReferenceAdapter $entityReference,
    ) {
    }

    public function toModel(TaskEntity $entity): TaskModel
    {
        $parentId = $entity->getParentId();

        return new TaskModel(
            id: UidUtils::toString($entity->getId()),
            name: $entity->getName(),
            parentId: $parentId !== null ? UidUtils::toString($parentId) : null,
            estimateTime: $entity->getEstimateTime(),
            status: $entity->getStatus(),
            createdAt: $entity->getCreatedAt(),
            updatedAt: $entity->getUpdatedAt(),
            cancelReason: $entity->getCancelReason(),
            finishedDate: $entity->getFinishedDate(),
            cancellationDate: $entity->getCancellationDate(),
        );
    }

    /**
     * @param list<TaskEntity> $entities
     *
     * @return list<TaskModel>
     */
    public function toModelArray(array $entities): array
    {
        return array_map(
            fn (TaskEntity $entity): TaskModel => $this->toModel($entity),
            $entities,
        );
    }

    /** @return list<TaskUserModel> */
    public function extractUsers(TaskEntity $entity): array
    {
        DoctrineCollectionAssertUtils::assertInitialized(
            $entity->getTaskUsers(),
            TaskEntity::class.'::'.TaskEntity::FIELD_TASK_USERS,
        );

        $users = [];

        foreach ($entity->getTaskUsers() as $taskUserEntity) {
            $users[] = new TaskUserModel(
                userId: UidUtils::toString($taskUserEntity->getUserId()),
                status: $taskUserEntity->getStatus(),
            );
        }

        return $users;
    }

    public function toLightModel(TaskEntity $entity): TaskLightModel
    {
        return TaskLightModelMapper::fromModel($this->toModel($entity));
    }

    public function toViewModel(TaskEntity $entity): TaskViewModel
    {
        $task = $this->toModel($entity);
        $users = $this->extractUsers($entity);

        return new TaskViewModel(
            id: $task->id,
            name: $task->name,
            parentId: $task->parentId,
            estimateTime: $task->estimateTime,
            status: $task->status,
            createdAt: DateUtils::toAtom($task->createdAt),
            users: $users,
            cancelReason: $task->cancelReason,
            finishedDate: DateUtils::toAtomOrNull($task->finishedDate),
            cancellationDate: DateUtils::toAtomOrNull($task->cancellationDate),
        );
    }

    /**
     * @param list<TaskEntity> $entities
     *
     * @return list<TaskViewModel>
     */
    public function toViewModelArray(array $entities): array
    {
        return array_map(
            fn (TaskEntity $entity): TaskViewModel => $this->toViewModel($entity),
            $entities,
        );
    }

    /**
     * @param list<TaskUserModel>|null $users
     */
    public function toEntity(
        TaskModel $task,
        ?TaskEntity $entity = null,
        ?array $users = null,
    ): TaskEntity {
        $entity ??= new TaskEntity();
        $entity->setId(Uuid::fromString($task->id));

        if ($task->parentId !== null) {
            $entity->setParent(
                $this->entityReference->getReference(TaskEntity::class, Uuid::fromString($task->parentId)),
            );
        } else {
            $entity->setParent(null);
        }

        $entity
            ->setName($task->name)
            ->setEstimateTime($task->estimateTime)
            ->setStatus($task->status)
            ->setCreatedAt($task->createdAt)
            ->setUpdatedAt($task->updatedAt)
            ->setCancelReason($task->cancelReason)
            ->setFinishedDate($task->finishedDate)
            ->setCancellationDate($task->cancellationDate);

        if ($users !== null) {
            $this->syncTaskUsers($entity, $users);
        }

        return $entity;
    }

    /**
     * @param list<TaskUserModel> $users
     */
    private function syncTaskUsers(TaskEntity $entity, array $users): void
    {
        DoctrineCollectionAssertUtils::assertInitialized(
            $entity->getTaskUsers(),
            TaskEntity::class.'::'.TaskEntity::FIELD_TASK_USERS,
        );

        /** @var array<string, TaskUserEntity> $existingByUserId */
        $existingByUserId = [];

        foreach ($entity->getTaskUsers() as $taskUserEntity) {
            $existingByUserId[UidUtils::toString($taskUserEntity->getUserId())] = $taskUserEntity;
        }

        $desiredUserIds = [];

        foreach ($users as $taskUser) {
            $desiredUserIds[$taskUser->userId] = true;

            if (isset($existingByUserId[$taskUser->userId])) {
                $existingByUserId[$taskUser->userId]->setStatus($taskUser->status);
                continue;
            }

            $taskUserEntity = new TaskUserEntity();
            $taskUserEntity->setUser(
                $this->entityReference->getReference(UserEntity::class, Uuid::fromString($taskUser->userId)),
            );
            $taskUserEntity->setStatus($taskUser->status);
            $entity->addTaskUser($taskUserEntity);
        }

        foreach ($existingByUserId as $userId => $taskUserEntity) {
            if (!isset($desiredUserIds[$userId])) {
                $entity->getTaskUsers()->removeElement($taskUserEntity);
            }
        }
    }
}
