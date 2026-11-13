<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Application\Exception\ErrorCodeEnum;
use App\Application\Model\User\Action\UserLoginModel;
use App\Application\Model\User\Action\UserLogoutModel;
use App\Application\Model\User\Action\UserRefreshModel;
use App\Application\Model\User\Action\UserRegisterModel;
use App\Application\Model\User\Read\UserLoginResponseModel;
use App\Application\Model\User\Read\UserRegisterResponseModel;
use App\Application\Model\User\UserLocationModel;
use App\Tests\DatabaseWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

final class AuthControllerTest extends DatabaseWebTestCase
{
    public function testRegisterAndLoginFlow(): void
    {
        $client = $this->createClientAndResetDatabase();

        $register = new UserRegisterModel(
            email: 'john@example.com',
            password: 'secret123',
            fname: 'John',
            lname: 'Doe',
        );

        $this->requestJson($client, 'POST', '/api/auth/register', $register->toArray());

        $this->assertSame(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        $registerModel = $this->decodeModel($client, UserRegisterResponseModel::class);
        $this->assertSame($register->email, $registerModel->email);
        $this->assertNull($registerModel->location);

        $loginModel = $this->login($client, $register->email, $register->password);

        $this->assertNotEmpty($loginModel->token);
        $this->assertNotEmpty($loginModel->refreshToken);
        $this->assertSame('Bearer', $loginModel->tokenType);
        $this->assertSame(900, $loginModel->expiresIn);
    }

    public function testRegisterRejectsShortPassword(): void
    {
        $client = $this->createClientAndResetDatabase();

        $this->requestJson($client, 'POST', '/api/auth/register', [
            'email' => 'short@example.com',
            'password' => '12345',
        ]);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());

        $error = $this->decodeErrorModel($client);
        $this->assertSame(ErrorCodeEnum::ValidationError, $error->codeId);
    }

    public function testLoginRejectsWrongPassword(): void
    {
        $client = $this->createClientAndResetDatabase();

        $this->requestJson($client, 'POST', '/api/auth/register', [
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);
        $this->assertSame(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        $this->requestJson($client, 'POST', '/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());

        $error = $this->decodeErrorModel($client);
        $this->assertSame(ErrorCodeEnum::InvalidCredentials, $error->codeId);
    }

    public function testLoginRejectsUnknownEmail(): void
    {
        $client = $this->createClientAndResetDatabase();

        $this->requestJson($client, 'POST', '/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'secret123',
        ]);

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());

        $error = $this->decodeErrorModel($client);
        $this->assertSame(ErrorCodeEnum::InvalidCredentials, $error->codeId);
    }

    public function testRefreshRotatesToken(): void
    {
        $client = $this->createClientAndResetDatabase();
        $login = $this->registerAndLogin($client, 'refresh@example.com');

        $this->requestJson($client, 'POST', '/api/auth/refresh', (new UserRefreshModel(
            refreshToken: $login->refreshToken,
        ))->toArray());

        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $refreshed = $this->decodeModel($client, UserLoginResponseModel::class);
        $this->assertNotSame($login->refreshToken, $refreshed->refreshToken);
        $this->assertNotEmpty($refreshed->token);

        $this->requestJson($client, 'POST', '/api/auth/refresh', (new UserRefreshModel(
            refreshToken: $login->refreshToken,
        ))->toArray());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
        $this->assertSame(
            ErrorCodeEnum::InvalidRefreshToken,
            $this->decodeErrorModel($client)->codeId,
        );
    }

    public function testRefreshReuseRevokesFamily(): void
    {
        $client = $this->createClientAndResetDatabase();
        $login = $this->registerAndLogin($client, 'reuse@example.com');

        $this->requestJson($client, 'POST', '/api/auth/refresh', (new UserRefreshModel(
            refreshToken: $login->refreshToken,
        ))->toArray());

        $refreshed = $this->decodeModel($client, UserLoginResponseModel::class);

        $this->requestJson($client, 'POST', '/api/auth/refresh', (new UserRefreshModel(
            refreshToken: $login->refreshToken,
        ))->toArray());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());

        $this->requestJson($client, 'POST', '/api/auth/refresh', (new UserRefreshModel(
            refreshToken: $refreshed->refreshToken,
        ))->toArray());
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testLogoutRevokesRefreshToken(): void
    {
        $client = $this->createClientAndResetDatabase();
        $login = $this->registerAndLogin($client, 'logout@example.com');

        $this->requestJson($client, 'POST', '/api/auth/logout', (new UserLogoutModel(
            refreshToken: $login->refreshToken,
        ))->toArray());

        $this->assertSame(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

        $this->requestJson($client, 'POST', '/api/auth/refresh', (new UserRefreshModel(
            refreshToken: $login->refreshToken,
        ))->toArray());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testLogoutIsIdempotent(): void
    {
        $client = $this->createClientAndResetDatabase();

        $this->requestJson($client, 'POST', '/api/auth/logout', [
            'refreshToken' => 'rt_unknown',
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
    }

    public function testRegisterDuplicateEmailReturnsConflict(): void
    {
        $client = $this->createClientAndResetDatabase();

        $register = new UserRegisterModel(
            email: 'duplicate@example.com',
            password: 'secret123',
            fname: 'John',
            lname: 'Doe',
        );

        $this->requestJson($client, 'POST', '/api/auth/register', $register->toArray());
        $this->requestJson($client, 'POST', '/api/auth/register', $register->toArray());

        $this->assertSame(Response::HTTP_CONFLICT, $client->getResponse()->getStatusCode());

        $error = $this->decodeErrorModel($client);
        $this->assertSame(ErrorCodeEnum::UserAlreadyExists, $error->codeId);
    }

    public function testRegisterWithCity(): void
    {
        $client = $this->createClientAndResetDatabase();

        $register = new UserRegisterModel(
            email: 'john@example.com',
            password: 'secret123',
            fname: 'John',
            lname: 'Doe',
            location: new UserLocationModel(city: 'Berlin'),
        );

        $this->requestJson($client, 'POST', '/api/auth/register', $register->toArray());

        $this->assertSame(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        $registerModel = $this->decodeModel($client, UserRegisterResponseModel::class);
        $this->assertSame($register->location?->city, $registerModel->location?->city);
    }

    public function testRegisterWithoutNames(): void
    {
        $client = $this->createClientAndResetDatabase();

        $register = new UserRegisterModel(
            email: 'noname@example.com',
            password: 'secret123',
        );

        $this->requestJson($client, 'POST', '/api/auth/register', $register->toArray());

        $this->assertSame(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        $registerModel = $this->decodeModel($client, UserRegisterResponseModel::class);
        $this->assertSame($register->email, $registerModel->email);
        $this->assertNull($registerModel->fname);
        $this->assertNull($registerModel->lname);
    }

    private function registerAndLogin(KernelBrowser $client, string $email): UserLoginResponseModel
    {
        $register = new UserRegisterModel(
            email: $email,
            password: 'secret123',
        );

        $this->requestJson($client, 'POST', '/api/auth/register', $register->toArray());
        $this->assertSame(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());

        return $this->login($client, $email, 'secret123');
    }

    private function login(KernelBrowser $client, string $email, string $password): UserLoginResponseModel
    {
        $this->requestJson($client, 'POST', '/api/auth/login', (new UserLoginModel(
            email: $email,
            password: $password,
        ))->toArray());

        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        return $this->decodeModel($client, UserLoginResponseModel::class);
    }
}
