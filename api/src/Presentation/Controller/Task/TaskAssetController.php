<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Task;

use App\Application\Service\Asset\Action\AssetAttachToTaskService;
use App\Application\Service\Asset\Action\AssetCreateService;
use App\Application\Service\Asset\Action\AssetDetachFromTaskService;
use App\Application\Model\Asset\Action\AssetCreateModel;
use App\Application\Service\Asset\Query\AssetListTaskQueryService;
use App\Application\Exception\Asset\AssetNotFoundException;
use App\Application\Exception\Asset\UnauthorizedAssetAccessException;
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

#[Route('/api/tasks/{taskId}/assets', name: 'task_assets_', requirements: ['taskId' => '[0-9a-fA-F-]{36}'])]
#[IsGranted('ROLE_USER')]
final class TaskAssetController extends BaseController
{
    public function __construct(
        private readonly AssetListTaskQueryService $listTaskAssets,
        private readonly AssetCreateService $createAsset,
        private readonly AssetAttachToTaskService $attachAsset,
        private readonly AssetDetachFromTaskService $detachAsset,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(string $taskId): JsonResponse
    {
        try {
            $assets = $this->listTaskAssets->execute(
                userId: $this->getCurrentUserId(),
                taskId: $taskId,
            );
        } catch (TaskNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedTaskAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return $this->json($assets);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(string $taskId, Request $request): JsonResponse
    {
        try {
            $asset = $this->createAsset->execute(
                AssetCreateModel::fromArray($this->requestPayload($request)),
                userId: $this->getCurrentUserId(),
                taskId: $taskId,
            );
        } catch (TaskNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedTaskAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return $this->json($asset, Response::HTTP_CREATED);
    }

    #[Route('/{id}/attach', name: 'attach', methods: ['POST'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function attach(string $taskId, string $id): JsonResponse
    {
        try {
            $asset = $this->attachAsset->execute(
                userId: $this->getCurrentUserId(),
                taskId: $taskId,
                assetId: $id,
            );
        } catch (TaskNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (AssetNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedTaskAccessException|UnauthorizedAssetAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return $this->json($asset);
    }

    #[Route('/{id}', name: 'detach', methods: ['DELETE'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function detach(string $taskId, string $id): JsonResponse
    {
        try {
            $this->detachAsset->execute(
                userId: $this->getCurrentUserId(),
                taskId: $taskId,
                assetId: $id,
            );
        } catch (TaskNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (AssetNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedTaskAccessException|UnauthorizedAssetAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
