<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Application\Model\User\Read\UserLoginResponseModel;
use App\Application\Model\User\Read\UserRegisterResponseModel;
use App\Application\Model\User\Action\UserLoginModel;
use App\Application\Model\User\Action\UserRegisterModel;
use App\Application\Model\User\UserLocationModel;
use App\Application\Exception\ErrorCodeEnum;
use App\Tests\DatabaseWebTestCase;
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

        $this->requestJson($client, 'POST', '/api/auth/login', (new UserLoginModel(
            email: $register->email,
            password: $register->password,
        ))->toArray());

        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $loginModel = $this->decodeModel($client, UserLoginResponseModel::class);
        $this->assertNotEmpty($loginModel->token);
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
}
