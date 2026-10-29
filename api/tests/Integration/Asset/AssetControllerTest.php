<?php

declare(strict_types=1);

namespace App\Tests\Integration\Asset;

use App\Application\Enum\Asset\AssetTypeEnum;
use App\Application\Exception\ErrorCodeEnum;
use App\Application\Model\Asset\Action\AssetCreateModel;
use App\Application\Model\Asset\Action\AssetUpdateModel;
use App\Application\Model\Asset\Read\AssetPageModel;
use App\Tests\AuthenticatedWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/** Presentation: HTTP status + error codeId. Use-case — Application Command/Query + TestBed. */
final class AssetControllerTest extends AuthenticatedWebTestCase
{
    public function testListAssetsReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createActiveTask($this->demoUser->getId(), 'Build feature');
        $this->bed->createAssetForTask($task->getId(), $this->demoUser->getId(), 'Laptop', 999.99);

        $this->client->request(
            'GET',
            '/api/assets',
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $page = $this->decodeModel($this->client, AssetPageModel::class);
        $this->assertSame(1, $page->page);
        $this->assertSame(20, $page->perPage);
        $this->assertSame(1, $page->total);
    }

    public function testListRejectsInvalidPerPage(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request('GET', '/api/assets?perPage=0', server: $this->demoAuthorizedServer());

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertSame(ErrorCodeEnum::ValidationError, $this->decodeErrorModel($this->client)->codeId);
    }

    public function testGetAssetReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createActiveTask($this->demoUser->getId(), 'Build feature');
        $asset = $this->bed->createAssetForTask($task->getId(), $this->demoUser->getId(), 'Laptop', 999.99);

        $this->client->request(
            'GET',
            '/api/assets/'.$asset->id,
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testAssetsRequireAuthentication(): void
    {
        $client = $this->createClientAndResetDatabase();

        $client->request('GET', '/api/assets');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testCreateAssetReturnsCreated(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createActiveTask($this->demoUser->getId(), 'Build feature');

        $this->requestJson(
            $this->client,
            'POST',
            '/api/assets',
            (new AssetCreateModel(
                name: 'Laptop',
                price: 999.99,
                type: AssetTypeEnum::Physical,
                taskIds: [$task->getId()],
            ))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateAssetReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createActiveTask($this->demoUser->getId(), 'Build feature');
        $asset = $this->bed->createAssetForTask($task->getId(), $this->demoUser->getId(), 'Laptop', 999.99);

        $this->requestJson(
            $this->client,
            'PUT',
            '/api/assets/'.$asset->id,
            (new AssetUpdateModel(name: 'Desktop', price: 1299.99))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteAssetReturnsNoContent(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createActiveTask($this->demoUser->getId(), 'Build feature');
        $asset = $this->bed->createAssetForTask($task->getId(), $this->demoUser->getId(), 'Laptop', 999.99);

        $this->client->request(
            'DELETE',
            '/api/assets/'.$asset->id,
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }
}
