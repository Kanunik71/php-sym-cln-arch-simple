<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Asset;

use App\Application\Service\Asset\Action\AssetCreateService;
use App\Application\Service\Asset\Action\AssetDeleteService;
use App\Application\Service\Asset\Action\AssetUpdateService;
use App\Application\Model\Asset\Action\AssetCreateModel;
use App\Application\Model\Asset\Action\AssetUpdateModel;
use App\Application\Service\Asset\Query\AssetQueryService;
use App\Application\Service\Asset\Query\AssetListQueryService;
use App\Application\Exception\Asset\AssetNotFoundException;
use App\Application\Exception\Asset\UnauthorizedAssetAccessException;
use App\Application\Exception\File\FileNotFoundException;
use App\Application\Exception\File\InvalidFileStateException;
use App\Application\Exception\File\UnauthorizedFileAccessException;
use App\Presentation\Controller\BaseController;
use App\Shared\Utils\Http\ErrorResponseUtils;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/assets', name: 'assets_')]
#[IsGranted('ROLE_USER')]
final class AssetController extends BaseController
{
    public function __construct(
        private readonly AssetListQueryService $listAssets,
        private readonly AssetCreateService $createAsset,
        private readonly AssetQueryService $getAsset,
        private readonly AssetUpdateService $updateAsset,
        private readonly AssetDeleteService $deleteAsset,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $assets = $this->listAssets->execute(
            userId: $this->getCurrentUserId(),
        );

        return $this->json($assets);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $asset = $this->createAsset->execute(
                AssetCreateModel::fromArray($this->requestPayload($request)),
                userId: $this->getCurrentUserId(),
            );
        } catch (FileNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedFileAccessException|InvalidFileStateException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return $this->json($asset, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function get(string $id): JsonResponse
    {
        try {
            $asset = $this->getAsset->execute(
                userId: $this->getCurrentUserId(),
                assetId: $id,
            );
        } catch (AssetNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedAssetAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return $this->json($asset);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $asset = $this->updateAsset->execute(
                AssetUpdateModel::fromArray($this->requestPayload($request)),
                userId: $this->getCurrentUserId(),
                assetId: $id,
            );
        } catch (AssetNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedAssetAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        } catch (FileNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedFileAccessException|InvalidFileStateException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return $this->json($asset);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        try {
            $this->deleteAsset->execute(
                userId: $this->getCurrentUserId(),
                assetId: $id,
            );
        } catch (AssetNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedAssetAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
