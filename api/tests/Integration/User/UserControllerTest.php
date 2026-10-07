<?php

declare(strict_types=1);

namespace App\Tests\Integration\User;

use App\Application\Model\User\Action\UserLoginModel;
use App\Application\Model\User\Action\UserUpdateModel;
use App\Application\Model\User\UserLocationModel;
use App\Application\Exception\ErrorCodeEnum;
use App\Shared\Utils\UidUtils;
use App\Tests\AuthenticatedWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/** Presentation: HTTP status + error codeId. Use-case — Application Command/Query + TestBed. */
final class UserControllerTest extends AuthenticatedWebTestCase
{
    public function testListUsersReturnsOk(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request('GET', '/api/users', server: $this->demoAuthorizedServer());

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testGetUserReturnsOk(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request(
            'GET',
            '/api/users/'.$this->demoUser->getId(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testGetUnknownUserReturnsNotFound(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request(
            'GET',
            '/api/users/'.UidUtils::nil(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertSame(ErrorCodeEnum::UserNotFound, $this->decodeErrorModel($this->client)->codeId);
    }

    public function testUsersRequireAuthentication(): void
    {
        $client = $this->createClientAndResetDatabase();

        $client->request('GET', '/api/users');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testUpdateUserReturnsOk(): void
    {
        $this->createAuthenticatedClient();

        $this->requestJson(
            $this->client,
            'PATCH',
            '/api/users/'.$this->demoUser->getId(),
            (new UserUpdateModel(
                fname: 'Jane',
                lname: 'Smith',
                email: $this->demoUser->getEmail()->toString(),
                location: new UserLocationModel(city: 'Berlin'),
                avatarFileId: $this->demoUser->getAvatarFileId(),
            ))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testCannotUpdateAnotherUser(): void
    {
        $this->createAuthenticatedClient();
        $otherUser = $this->createUser(email: 'other@example.com');

        $this->requestJson(
            $this->client,
            'PATCH',
            '/api/users/'.$otherUser->getId(),
            (new UserUpdateModel(
                fname: 'Hacked',
                lname: $otherUser->getLname(),
                email: $otherUser->getEmail()->toString(),
                avatarFileId: $otherUser->getAvatarFileId(),
            ))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        $this->assertSame(ErrorCodeEnum::UnauthorizedUserAccess, $this->decodeErrorModel($this->client)->codeId);
    }

    public function testUpdateDuplicateEmailReturnsConflict(): void
    {
        $this->createAuthenticatedClient();
        $this->createUser(email: 'taken@example.com');

        $this->requestJson(
            $this->client,
            'PATCH',
            '/api/users/'.$this->demoUser->getId(),
            (new UserUpdateModel(
                fname: $this->demoUser->getFname(),
                lname: $this->demoUser->getLname(),
                email: 'taken@example.com',
                avatarFileId: $this->demoUser->getAvatarFileId(),
            ))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
        $this->assertSame(ErrorCodeEnum::UserAlreadyExists, $this->decodeErrorModel($this->client)->codeId);
    }

    public function testCanUpdatePasswordAndLogin(): void
    {
        $this->createAuthenticatedClient();

        $this->requestJson(
            $this->client,
            'PATCH',
            '/api/users/'.$this->demoUser->getId(),
            (new UserUpdateModel(
                fname: $this->demoUser->getFname(),
                lname: $this->demoUser->getLname(),
                email: $this->demoUser->getEmail()->toString(),
                password: 'new-secret123',
                avatarFileId: $this->demoUser->getAvatarFileId(),
            ))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->requestJson(
            $this->client,
            'POST',
            '/api/auth/login',
            (new UserLoginModel(
                email: $this->demoUser->getEmail()->toString(),
                password: 'new-secret123',
            ))->toArray(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteUserReturnsNoContent(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request(
            'DELETE',
            '/api/users/'.$this->demoUser->getId(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testCannotDeleteAnotherUser(): void
    {
        $this->createAuthenticatedClient();
        $otherUser = $this->createUser(email: 'other@example.com');

        $this->client->request(
            'DELETE',
            '/api/users/'.$otherUser->getId(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        $this->assertSame(ErrorCodeEnum::UnauthorizedUserAccess, $this->decodeErrorModel($this->client)->codeId);
    }

    public function testQueryUsersReturnsOk(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request(
            'GET',
            '/api/users/query',
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testQueryInvalidFilterReturnsBadRequest(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request(
            'GET',
            '/api/users/query?'.http_build_query([
                'filter' => ['password' => ['eq' => 'secret']],
            ]),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testQueryMetaReturnsOk(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request(
            'GET',
            '/api/users/query/meta',
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('filters', $payload);
        self::assertArrayHasKey('sortFields', $payload);
        self::assertArrayHasKey('defaultSort', $payload);
        self::assertArrayHasKey('pagination', $payload);
    }
}
