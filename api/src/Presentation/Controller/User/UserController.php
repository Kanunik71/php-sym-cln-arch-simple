<?php

declare(strict_types=1);

namespace App\Presentation\Controller\User;

use App\Application\Service\User\Action\UserDeleteService;
use App\Application\Service\User\Action\UserUpdateService;
use App\Application\Model\User\Action\UserSearchModel;
use App\Application\Model\User\Action\UserUpdateModel;
use App\Application\Service\User\Query\UserQueryService;
use App\Application\Service\User\Query\UserListQueryService;
use App\Application\Service\User\Query\UserSearchQueryService;
use App\Application\Service\User\Query\UserSearchMetaQueryService;
use App\Application\Exception\File\FileNotFoundException;
use App\Application\Exception\File\InvalidFileStateException;
use App\Application\Exception\File\UnauthorizedFileAccessException;
use App\Application\Exception\User\UnauthorizedUserAccessException;
use App\Application\Exception\User\UserAlreadyExistsException;
use App\Application\Exception\User\UserNotFoundException;
use App\Presentation\Controller\BaseController;
use App\Shared\Utils\Http\ErrorResponseUtils;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users', name: 'users_')]
#[IsGranted('ROLE_USER')]
final class UserController extends BaseController
{
    public function __construct(
        private readonly UserListQueryService $listUsers,
        private readonly UserSearchQueryService $searchUsers,
        private readonly UserSearchMetaQueryService $searchUsersMeta,
        private readonly UserQueryService $getUser,
        private readonly UserUpdateService $updateUser,
        private readonly UserDeleteService $deleteUser,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $users = $this->listUsers->execute();

        return $this->json($users);
    }

    #[Route('/query', name: 'query', methods: ['GET'])]
    public function query(Request $request): JsonResponse
    {
        try {
            $page = $this->searchUsers->execute(
                UserSearchModel::fromArray($request->query->all()),
            );
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return $this->json($page);
    }

    #[Route('/query/meta', name: 'query_meta', methods: ['GET'])]
    public function queryMeta(): JsonResponse
    {
        $meta = $this->searchUsersMeta->execute();

        return $this->json($meta);
    }

    #[Route('/{id}', name: 'get', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        try {
            $user = $this->getUser->execute(userId: $id);
        } catch (UserNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        }

        return $this->json($user);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $user = $this->updateUser->execute(
                UserUpdateModel::fromArray($this->requestPayload($request)),
                userId: $id,
                currentUserId: $this->getCurrentUserId(),
            );
        } catch (UserNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedUserAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        } catch (UserAlreadyExistsException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_CONFLICT);
        } catch (FileNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedFileAccessException|InvalidFileStateException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return $this->json($user);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '[0-9a-fA-F-]{36}'], methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        try {
            $this->deleteUser->execute(
                userId: $id,
                currentUserId: $this->getCurrentUserId(),
            );
        } catch (UserNotFoundException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedUserAccessException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
