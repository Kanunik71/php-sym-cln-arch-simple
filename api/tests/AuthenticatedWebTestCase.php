<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application\Model\User\Read\UserLoginResponseModel;
use App\Application\Model\User\UserModel;
use App\Tests\Support\TestBed;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

abstract class AuthenticatedWebTestCase extends DatabaseWebTestCase
{
    protected KernelBrowser $client;

    protected UserModel $demoUser;

    protected string $token;

    protected function createAuthenticatedClient(
        ?string $email = null,
        ?string $password = null,
        ?string $fname = null,
        ?string $lname = null,
    ): KernelBrowser {
        $this->client = $this->createClientAndResetDatabase();

        $password ??= TestBed::DEMO_PASSWORD;
        $this->demoUser = $this->bed->seedDefaultUser(
            email: $email,
            password: $password,
            fname: $fname,
            lname: $lname,
        );
        $this->token = $this->loginAs($this->demoUser->getEmail()->toString(), $password);

        return $this->client;
    }

    /** @return array<string, string> */
    protected function demoAuthorizedServer(): array
    {
        return $this->authorizedServer($this->token);
    }

    protected function createUser(
        string $email,
        string $password = TestBed::DEMO_PASSWORD,
        ?string $fname = null,
        ?string $lname = null,
        ?string $city = null,
        ?DateTimeImmutable $createdAt = null,
    ): UserModel {
        return $this->bed->createUser(
            email: $email,
            password: $password,
            fname: $fname,
            lname: $lname,
            city: $city,
            createdAt: $createdAt,
        );
    }

    protected function loginAs(string $email, string $password = TestBed::DEMO_PASSWORD): string
    {
        $this->requestRawJson($this->client, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        return $this->decodeModel($this->client, UserLoginResponseModel::class)->token;
    }
}
