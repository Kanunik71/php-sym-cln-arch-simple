<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Auth;

use App\Application\Exception\Auth\InvalidRefreshTokenException;
use App\Application\Exception\User\InvalidCredentialsException;
use App\Application\Exception\User\UserAlreadyExistsException;
use App\Application\Model\User\Action\UserLoginModel;
use App\Application\Model\User\Action\UserLogoutModel;
use App\Application\Model\User\Action\UserRefreshModel;
use App\Application\Model\User\Action\UserRegisterModel;
use App\Application\Service\User\Action\UserLoginService;
use App\Application\Service\User\Action\UserLogoutService;
use App\Application\Service\User\Action\UserRefreshService;
use App\Application\Service\User\Action\UserRegisterService;
use App\Presentation\Controller\BaseController;
use App\Shared\Utils\Http\ErrorResponseUtils;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth', name: 'auth_')]
final class AuthController extends BaseController
{
    public function __construct(
        private readonly UserRegisterService $registerUser,
        private readonly UserLoginService $loginUser,
        private readonly UserRefreshService $refreshUser,
        private readonly UserLogoutService $logoutUser,
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $result = $this->registerUser->execute(
                UserRegisterModel::fromArray($this->requestPayload($request)),
            );
        } catch (UserAlreadyExistsException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_CONFLICT);
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return $this->json($result, Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {
            $result = $this->loginUser->execute(
                UserLoginModel::fromArray($this->requestPayload($request)),
            );
        } catch (InvalidCredentialsException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_UNAUTHORIZED);
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return $this->json($result);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        try {
            $result = $this->refreshUser->execute(
                UserRefreshModel::fromArray($this->requestPayload($request)),
            );
        } catch (InvalidRefreshTokenException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_UNAUTHORIZED);
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return $this->json($result);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->logoutUser->execute(
                UserLogoutModel::fromArray($this->requestPayload($request)),
            );
        } catch (InvalidArgumentException $exception) {
            return ErrorResponseUtils::json($exception, Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
