<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Auth;

use App\Application\Service\User\Action\UserLoginService;
use App\Application\Service\User\Action\UserRegisterService;
use App\Application\Model\User\Action\UserLoginModel;
use App\Application\Model\User\Action\UserRegisterModel;
use App\Application\Exception\User\InvalidCredentialsException;
use App\Application\Exception\User\UserAlreadyExistsException;
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
}
