<?php

declare(strict_types=1);

namespace App\Tests\Utils\Http;

use App\Application\Exception\ErrorCodeEnum;
use App\Application\Exception\User\UserAlreadyExistsException;
use App\Shared\Utils\Http\ErrorResponseUtils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class ErrorResponseUtilsTest extends TestCase
{
    public function testBuildsBodyFromCodedException(): void
    {
        $exception = UserAlreadyExistsException::withEmail('john@example.com');

        $body = ErrorResponseUtils::body($exception);

        $this->assertSame('User with email "john@example.com" already exists.', $body->error);
        $this->assertSame(ErrorCodeEnum::UserAlreadyExists, $body->codeId);
    }

    public function testBuildsBodyFromInvalidArgumentException(): void
    {
        $exception = new InvalidArgumentException('Field "name" is required.');

        $body = ErrorResponseUtils::body($exception);

        $this->assertSame('Field "name" is required.', $body->error);
        $this->assertSame(ErrorCodeEnum::ValidationError, $body->codeId);
    }

    public function testBuildsJsonResponseWithStatus(): void
    {
        $exception = UserAlreadyExistsException::withEmail('john@example.com');

        $response = ErrorResponseUtils::json($exception, Response::HTTP_CONFLICT);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame([
            'error' => 'User with email "john@example.com" already exists.',
            'codeId' => 'USER_ALREADY_EXISTS',
        ], json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR));
    }
}
