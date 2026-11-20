<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Model\Http\ErrorResponseModel;
use App\Application\Utils\Wire\InputAssertUtils;
use InvalidArgumentException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Throwable;

abstract class BaseController extends AbstractController
{
    protected function getCurrentUserId(): string
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface) {
            throw new LogicException('Authenticated user is missing.');
        }

        return $user->getUserIdentifier();
    }

    /** @return array<string, mixed> */
    protected function requestPayload(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON payload.');
        }

        return InputAssertUtils::stringKeyedArray($data, 'payload');
    }

    /**
     * Endpoint failure: the body is built by `ErrorResponseModel::fromThrowable` (Application);
     * here only the HTTP wrapper and the status from the method's spec.
     */
    protected function errorResponse(Throwable $exception, int $status): JsonResponse
    {
        return new JsonResponse(ErrorResponseModel::fromThrowable($exception)->toArray(), $status);
    }
}
