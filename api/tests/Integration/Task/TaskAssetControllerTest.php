<?php

declare(strict_types=1);

namespace App\Tests\Integration\Task;

use App\Application\Model\Asset\Action\AssetCreateModel;
use App\Application\Enum\Asset\AssetTypeEnum;
use App\Shared\Utils\UidUtils;
use App\Tests\AuthenticatedWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/** Presentation: HTTP status + error codeId. Use-case — Application Command/Query + TestBed. */
final class TaskAssetControllerTest extends AuthenticatedWebTestCase
{
    public function testListAssetsReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createActiveTask($this->demoUser->getId(), 'Build feature');
        $this->bed->createAssetForTask($task->getId(), $this->demoUser->getId(), 'Laptop', 999.99);

        $this->client->request(
            'GET',
            '/api/tasks/'.$task->getId().'/assets',
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateAssetReturnsCreated(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createActiveTask($this->demoUser->getId(), 'Build feature');

        $this->requestJson(
            $this->client,
            'POST',
            '/api/tasks/'.$task->getId().'/assets',
            (new AssetCreateModel(name: 'Laptop', price: 999.99, type: AssetTypeEnum::Physical))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    public function testAttachAssetReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createActiveTask($this->demoUser->getId(), 'Build feature');
        $otherTask = $this->bed->createActiveTask($this->demoUser->getId(), 'Other task');
        $asset = $this->bed->createAssetForTask($otherTask->getId(), $this->demoUser->getId(), 'Laptop', 999.99);

        $this->client->request(
            'POST',
            '/api/tasks/'.$task->getId().'/assets/'.$asset->id.'/attach',
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testDetachAssetReturnsNoContent(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createActiveTask($this->demoUser->getId(), 'Build feature');
        $asset = $this->bed->createAssetForTask($task->getId(), $this->demoUser->getId(), 'Laptop', 999.99);

        $this->client->request(
            'DELETE',
            '/api/tasks/'.$task->getId().'/assets/'.$asset->id,
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testTaskAssetsRequireAuthentication(): void
    {
        $client = $this->createClientAndResetDatabase();

        $client->request('GET', '/api/tasks/'.UidUtils::nil().'/assets');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }
}
