<?php

declare(strict_types=1);

namespace App\Tests\Integration\Notification;

use App\Application\Model\Notification\Read\NotificationPreferenceViewModel;
use App\Application\Model\Notification\Action\UpdateNotificationPreferencesModel;
use App\Application\Enum\Notification\NotificationChannelEnum;
use App\Application\Enum\Notification\NotificationTypeEnum;
use App\Tests\AuthenticatedWebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class NotificationPreferenceControllerTest extends AuthenticatedWebTestCase
{
    public function testGetPreferencesReturnsOk(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request(
            'GET',
            '/api/users/me/notification-preferences',
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdatePreferencesReturnsOk(): void
    {
        $this->createAuthenticatedClient();

        $payload = (new UpdateNotificationPreferencesModel([
            new NotificationPreferenceViewModel(
                type: NotificationTypeEnum::UserUpdated,
                enabled: true,
                channels: [NotificationChannelEnum::Email],
            ),
        ]))->toArray();

        $this->requestRawJson(
            $this->client,
            'PUT',
            '/api/users/me/notification-preferences',
            $payload,
            $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdatePreferencesRejectsTransactionalType(): void
    {
        $this->createAuthenticatedClient();

        $payload = (new UpdateNotificationPreferencesModel([
            new NotificationPreferenceViewModel(
                type: NotificationTypeEnum::UserRegistered,
                enabled: false,
                channels: [],
            ),
        ]))->toArray();

        $this->requestRawJson(
            $this->client,
            'PUT',
            '/api/users/me/notification-preferences',
            $payload,
            $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testRequiresAuthentication(): void
    {
        $client = $this->createClientAndResetDatabase();

        $client->request('GET', '/api/users/me/notification-preferences');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }
}
