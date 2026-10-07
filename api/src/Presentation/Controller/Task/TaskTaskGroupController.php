<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Task;

use App\Application\Service\TaskGroup\Action\TaskGroupAttachTaskService;
use App\Application\Service\TaskGroup\Action\TaskGroupCreateService;
use App\Application\Service\TaskGroup\Action\TaskGroupDeleteService;
use App\Application\Service\TaskGroup\Action\TaskGroupRemoveTaskService;
use App\Application\Model\TaskGroup\Action\TaskGroupCreateModel;
use App\Application\Service\TaskGroup\Query\TaskGroupQueryService;
use App\Application\Service\TaskGroup\Query\TaskGroupListQueryService;
use App\Application\Exception\Task\TaskNotFoundException;
use App\Application\Exception\Task\UnauthorizedTaskAccessException;
use App\Application\Exception\TaskGroup\TaskGroupNotFoundException;
use App\Application\Exception\TaskGroup\UnauthorizedTaskGroupAccessException;
use App\Presentation\Controller\BaseController;
use App\Shared\Utils\Http\ErrorResponseUtils;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/tasks/{taskId}/task-groups', name: 'task_task_groups_', requirements: ['taskId' => '[0-9a-fA-F-]{36}'])]
#[IsGranted('ROLE_USER')]
final class TaskTaskGroupController extends BaseController
{
    public function __construct(
        private readonly TaskGroupListQueryService $listTaskGroups,
        private readonly TaskGroupCreateService $createTaskGroup,
        private readonly TaskGroupAttachTaskService $attachTaskToTaskGroup,
        private readonly TaskGroupQueryService $getTaskGroup,
        private readonly TaskGroupDeleteService $deleteTaskGroup,
        private readonly TaskGroupRemoveTaskService $removeTaskFromTaskGroup,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $taskId): JsonResponse
    {
        try {
            $taskGroups = $this->listTaskGroups->execute(
                userId: $this->getCurrentUserId(),
                taskId: $taskId,
            );
        } catch (TaskNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedTaskAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return $this->json($taskGroups);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $taskId, Request $request): JsonResponse
    {
        try {
            $payload = TaskGroupCreateModel::fromArray($this->requestPayload($request));
            $taskGroup = $this->createTaskGroup->execute(
                new TaskGroupCreateModel(name: $payload->name, taskIds: [$taskId]),
                userId: $this->getCurrentUserId(),
            );
        } catch (TaskNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedTaskAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return $this->json($taskGroup, Response::HTTP_CREATED);
    }

    #[Route('/{id}/attach', name: 'attach', methods: ['POST'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function attach(string $taskId, string $id): JsonResponse
    {
        try {
            $taskGroup = $this->attachTaskToTaskGroup->execute(
                userId: $this->getCurrentUserId(),
                taskId: $taskId,
                taskGroupId: $id,
            );
        } catch (TaskNotFoundException|TaskGroupNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedTaskAccessException|UnauthorizedTaskGroupAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return $this->json($taskGroup);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function get(string $taskId, string $id): JsonResponse
    {
        try {
            $taskGroup = $this->getTaskGroup->execute(
                userId: $this->getCurrentUserId(),
                taskId: $taskId,
                taskGroupId: $id,
            );
        } catch (TaskNotFoundException|TaskGroupNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedTaskAccessException|UnauthorizedTaskGroupAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return $this->json($taskGroup);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function delete(string $taskId, string $id): JsonResponse
    {
        try {
            $this->deleteTaskGroup->execute(
                userId: $this->getCurrentUserId(),
                taskId: $taskId,
                taskGroupId: $id,
            );
        } catch (TaskNotFoundException|TaskGroupNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedTaskAccessException|UnauthorizedTaskGroupAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/tasks/{removeTaskId}', name: 'remove_task', methods: ['DELETE'], requirements: ['id' => '[0-9a-fA-F-]{36}', 'removeTaskId' => '[0-9a-fA-F-]{36}'])]
    public function removeTask(string $taskId, string $id, string $removeTaskId): JsonResponse
    {
        try {
            $taskGroup = $this->removeTaskFromTaskGroup->execute(
                userId: $this->getCurrentUserId(),
                taskId: $taskId,
                taskGroupId: $id,
                removeTaskId: $removeTaskId,
            );
        } catch (TaskNotFoundException|TaskGroupNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedTaskAccessException|UnauthorizedTaskGroupAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return $this->json($taskGroup);
    }
}
