<?php

declare(strict_types=1);

namespace App\Tests\Integration\Platform;

use App\Application\Exception\ErrorCodeEnum;
use App\Tests\AuthenticatedWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Presentation: идентификатор в path — 404, если не совпадает с маршрутом,
 * 400, если совпадает, но не разбирается как UUID.
 */
final class PathIdentifierTest extends AuthenticatedWebTestCase
{
    public function testRejectsIdentifierNotMatchingRouteFormat(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request(
            'GET',
            '/api/users/not-a-uuid',
            server: $this->demoAuthorizedServer(),
        );

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(ErrorCodeEnum::ValidationError, $this->decodeErrorModel($this->client)->codeId);
    }

    public function testRejectsIdentifierNotParsableAsUuid(): void
    {
        $this->createAuthenticatedClient();

        // 36 hex-символов без дефисов — совпадает с форматом маршрута, но не UUID.
        $this->client->request(
            'GET',
            '/api/users/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            server: $this->demoAuthorizedServer(),
        );

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame(ErrorCodeEnum::ValidationError, $this->decodeErrorModel($this->client)->codeId);
        $this->assertSame('Invalid UUID.', $this->decodeErrorModel($this->client)->error);
    }

    public function testReturnsNotFoundForUnknownButValidUuid(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request(
            'GET',
            '/api/users/00000000-0000-0000-0000-000000000000',
            server: $this->demoAuthorizedServer(),
        );

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(ErrorCodeEnum::UserNotFound, $this->decodeErrorModel($this->client)->codeId);
    }
}
