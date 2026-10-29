<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Application\Exception\ErrorCodeEnum;
use App\Application\Model\Http\ErrorResponseModel;
use App\Shared\Utils\Http\ErrorResponseUtils;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private bool $debug,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // After the security listener (priority 1), before the error page (-128).
            KernelEvents::EXCEPTION => ['onException', -1],
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->isApi($event)) {
            return;
        }

        if ($event->hasResponse()) {
            if ($event->getResponse()->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
                $event->stopPropagation();
            }

            return;
        }

        $event->setResponse($this->responseFor($event->getThrowable()));
        $event->stopPropagation();
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $response = $event->getResponse();
        if ($response->getStatusCode() !== Response::HTTP_UNAUTHORIZED || $this->isErrorModel($response)) {
            return;
        }

        $response->setContent((string) json_encode(
            (new ErrorResponseModel(
                error: $this->unauthorizedMessage($response),
                codeId: ErrorCodeEnum::InvalidCredentials,
            ))->toArray(),
            JSON_THROW_ON_ERROR,
        ));
        $response->headers->set('Content-Type', 'application/json');
    }

    private function isApi(ExceptionEvent $event): bool
    {
        return str_starts_with($event->getRequest()->getPathInfo(), '/api');
    }

    private function responseFor(Throwable $throwable): JsonResponse
    {
        $status = $this->status($throwable);
        if ($status === Response::HTTP_UNAUTHORIZED) {
            $message = $throwable->getMessage() !== '' ? $throwable->getMessage() : 'Authentication required.';

            return new JsonResponse((new ErrorResponseModel(
                error: $message,
                codeId: ErrorCodeEnum::InvalidCredentials,
            ))->toArray(), $status);
        }

        if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR && !$this->debug) {
            return new JsonResponse((new ErrorResponseModel(
                error: 'Internal server error.',
                codeId: ErrorCodeEnum::ValidationError,
            ))->toArray(), $status);
        }

        return ErrorResponseUtils::json($throwable, $status);
    }

    private function status(Throwable $throwable): int
    {
        if ($throwable instanceof HttpExceptionInterface) {
            return $throwable->getStatusCode();
        }

        if ($throwable instanceof AuthenticationException) {
            return Response::HTTP_UNAUTHORIZED;
        }

        if ($throwable instanceof AccessDeniedException) {
            return Response::HTTP_FORBIDDEN;
        }

        if ($throwable instanceof InvalidArgumentException) {
            return Response::HTTP_BAD_REQUEST;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function isErrorModel(Response $response): bool
    {
        $data = $this->jsonBody($response);

        return is_array($data)
            && is_string($data['error'] ?? null)
            && is_string($data['codeId'] ?? null);
    }

    private function unauthorizedMessage(Response $response): string
    {
        $data = $this->jsonBody($response);
        if (!is_array($data)) {
            return 'Authentication required.';
        }

        foreach (['message', 'detail', 'error'] as $key) {
            $value = $data[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return 'Authentication required.';
    }

    /** @return array<mixed>|null */
    private function jsonBody(Response $response): ?array
    {
        try {
            $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($data) ? $data : null;
    }
}
