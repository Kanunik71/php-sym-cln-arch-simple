<?php

declare(strict_types=1);

namespace App\Presentation\Controller\TaskGroup;

use App\Application\Service\TaskGroup\Action\TaskGroupCreateService;
use App\Application\Model\TaskGroup\Action\TaskGroupCreateModel;
use App\Application\Service\TaskGroup\Query\TaskGroupSummarizeQueryService;
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

#[Route('/api/task-groups', name: 'task_groups_')]
#[IsGranted('ROLE_USER')]
final class TaskGroupController extends BaseController
{
    public function __construct(
        private readonly TaskGroupCreateService $createTaskGroup,
        private readonly TaskGroupSummarizeQueryService $summarizeTaskGroups,
    ) {
    }

    #[Route('/summary', name: 'summary', methods: ['GET'])]
    public function summary(): JsonResponse
    {
        $summary = $this->summarizeTaskGroups->execute();

        return $this->json($summary);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $taskGroup = $this->createTaskGroup->execute(
                TaskGroupCreateModel::fromArray($this->requestPayload($request)),
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
}
