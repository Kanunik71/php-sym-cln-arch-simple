<?php

declare(strict_types=1);

namespace App\Tests\Integration\Platform;

use App\Application\Exception\ErrorCodeEnum;
use App\Shared\Utils\UidUtils;
use App\Tests\AuthenticatedWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Presentation: an identifier in the path is 404 when it does not match the route,
 * and 400 when it matches but does not parse as a UUID.
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

        // 36 hex characters without hyphens — matches the route format but is not a UUID.
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
            '/api/users/'.UidUtils::NIL,
            server: $this->demoAuthorizedServer(),
        );

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(ErrorCodeEnum::UserNotFound, $this->decodeErrorModel($this->client)->codeId);
    }
}
