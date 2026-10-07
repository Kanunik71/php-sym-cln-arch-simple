<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Task;

use App\Application\Service\Task\Action\TaskChangeStatusService;
use App\Application\Service\Task\Action\TaskCreateService;
use App\Application\Service\Task\Action\TaskDeleteService;
use App\Application\Model\Task\Action\TaskChangeStatusModel;
use App\Application\Model\Task\Action\TaskCreateModel;
use App\Application\Service\Task\Query\TaskQueryService;
use App\Application\Service\Task\Query\TaskListQueryService;
use App\Application\Exception\Task\InvalidTaskStatusTransitionException;
use App\Application\Exception\Task\TaskNotFoundException;
use App\Application\Exception\Task\UnauthorizedTaskAccessException;
use App\Presentation\Controller\BaseController;
use App\Shared\Utils\Http\ErrorResponseUtils;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/tasks', name: 'tasks_')]
#[IsGranted('ROLE_USER')]
final class TaskController extends BaseController
{
    public function __construct(
        private readonly TaskListQueryService $listTasks,
        private readonly TaskCreateService $createTask,
        private readonly TaskQueryService $getTask,
        private readonly TaskChangeStatusService $changeTaskStatus,
        private readonly TaskDeleteService $deleteTask,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $tasks = $this->listTasks->execute(
            userId: $this->getCurrentUserId(),
        );

        return $this->json($tasks);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $task = $this->createTask->execute(
                TaskCreateModel::fromArray($this->requestPayload($request)),
                userId: $this->getCurrentUserId(),
            );
        } catch (TaskNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return $this->json($task, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function get(string $id): JsonResponse
    {
        try {
            $task = $this->getTask->execute(
                taskId: $id,
                userId: $this->getCurrentUserId(),
            );
        } catch (TaskNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedTaskAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return $this->json($task);
    }

    #[Route('/{id}/status', name: 'change_status', methods: ['PATCH'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function changeStatus(string $id, Request $request): JsonResponse
    {
        try {
            $task = $this->changeTaskStatus->execute(
                TaskChangeStatusModel::fromArray($this->requestPayload($request)),
                taskId: $id,
                userId: $this->getCurrentUserId(),
            );
        } catch (TaskNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedTaskAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        } catch (InvalidTaskStatusTransitionException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_CONFLICT);
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return $this->json($task);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function delete(string $id): JsonResponse
    {
        try {
            $this->deleteTask->execute(
                taskId: $id,
                userId: $this->getCurrentUserId(),
            );
        } catch (TaskNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedTaskAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
