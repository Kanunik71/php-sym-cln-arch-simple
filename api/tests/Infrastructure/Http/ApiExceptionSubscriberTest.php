<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Http;

use App\Application\Exception\ErrorCodeEnum;
use App\Infrastructure\Http\ApiExceptionSubscriber;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

final class ApiExceptionSubscriberTest extends TestCase
{
    public function testInvalidArgumentBecomesValidationError(): void
    {
        $subscriber = new ApiExceptionSubscriber(debug: false);
        $event = $this->exceptionEvent('/api/users/not-a-uuid', new InvalidArgumentException('Invalid UUID.'));

        $subscriber->onException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame([
            'error' => 'Invalid UUID.',
            'codeId' => ErrorCodeEnum::ValidationError->value,
        ], json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR));
    }

    public function testHidesInternalMessageOutsideDebug(): void
    {
        $subscriber = new ApiExceptionSubscriber(debug: false);
        $event = $this->exceptionEvent('/api/users', new RuntimeException('SQLSTATE connection refused'));

        $subscriber->onException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame([
            'error' => 'Internal server error.',
            'codeId' => ErrorCodeEnum::ValidationError->value,
        ], json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR));
    }

    public function testRewritesSecurityUnauthorizedBody(): void
    {
        $subscriber = new ApiExceptionSubscriber(debug: true);
        $request = Request::create('/api/users');
        $response = new JsonResponse(['code' => 401, 'message' => 'JWT Token not found'], Response::HTTP_UNAUTHORIZED);
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $subscriber->onResponse($event);

        self::assertSame([
            'error' => 'JWT Token not found',
            'codeId' => ErrorCodeEnum::InvalidCredentials->value,
        ], json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR));
    }

    public function testLeavesApplicationUnauthorizedBody(): void
    {
        $subscriber = new ApiExceptionSubscriber(debug: true);
        $response = new JsonResponse([
            'error' => 'Invalid refresh token.',
            'codeId' => ErrorCodeEnum::InvalidRefreshToken->value,
        ], Response::HTTP_UNAUTHORIZED);
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/api/auth/refresh'),
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $subscriber->onResponse($event);

        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertSame(ErrorCodeEnum::InvalidRefreshToken->value, $payload['codeId']);
    }

    private function exceptionEvent(string $path, Throwable $throwable): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create($path),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }
}
