<?php

declare(strict_types=1);

namespace App\Tests\Integration\File;

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
}
