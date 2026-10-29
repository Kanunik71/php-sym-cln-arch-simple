<?php

declare(strict_types=1);

namespace App\Tests\Integration\File;

use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Enum\Task\TaskUserStatusEnum;
use App\Application\Exception\ErrorCodeEnum;
use App\Application\Model\Asset\Read\AssetViewModel;
use App\Application\Model\Task\TaskUserModel;
use App\Tests\AuthenticatedWebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class FileControllerTest extends AuthenticatedWebTestCase
{
    public function testFilesRequireAuthentication(): void
    {
        $client = $this->createClientAndResetDatabase();

        $client->request('GET', '/api/files/00000000-0000-4000-8000-000000000001');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testSignedDownloadUrlWorksWithoutJwt(): void
    {
        $this->createAuthenticatedClient(email: 'owner@example.com');
        $other = $this->createUser(email: 'other@example.com');
        $file = $this->bed->uploadTmpImage($this->demoUser->getId());
        $task = $this->bed->createTask(
            ownerId: $this->demoUser->getId(),
            name: 'Shared',
            status: TaskStatusEnum::Active,
            estimateTime: 60,
            users: [
                TaskUserModel::create($this->demoUser->getId(), TaskUserStatusEnum::Active),
                TaskUserModel::create($other->getId(), TaskUserStatusEnum::Active),
            ],
        );
        $this->bed->createAsset(
            ownerId: $this->demoUser->getId(),
            name: 'Shot',
            taskIds: [$task->getId()],
            imageFileIds: [$file->id],
        );

        $otherToken = $this->loginAs('other@example.com');
        $this->client->request(
            'GET',
            '/api/tasks/'.$task->getId().'/assets',
            server: $this->authorizedServer($otherToken),
        );
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $assets = $this->decodeModelList($this->client, AssetViewModel::class);
        self::assertNotEmpty($assets);
        $url = $assets[0]->mainImage?->url;
        self::assertNotNull($url);
        $parts = parse_url($url);
        self::assertIsArray($parts);
        self::assertIsString($parts['path'] ?? null);

        $this->client->request('GET', $parts['path'].(isset($parts['query']) ? '?'.$parts['query'] : ''));

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertStringStartsWith('image/jpeg', (string) $this->client->getResponse()->headers->get('Content-Type'));

        $this->client->request('GET', '/api/files/'.$file->id.'/download');

        $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        $this->assertSame(ErrorCodeEnum::UnauthorizedFileAccess, $this->decodeErrorModel($this->client)->codeId);
    }
}
