<?php

declare(strict_types=1);

namespace App\Tests\Integration\TaskGroup;

use App\Application\Model\TaskGroup\Action\TaskGroupCreateModel;
use App\Tests\AuthenticatedWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/** Presentation: HTTP status + error codeId. Use-case — Application Command/Query + TestBed. */
final class TaskGroupControllerTest extends AuthenticatedWebTestCase
{
    public function testCreateTaskGroupReturnsCreated(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createTask(name: 'Build feature');

        $this->requestJson(
            $this->client,
            'POST',
            '/api/task-groups',
            (new TaskGroupCreateModel(
                name: 'Release 1.0',
                taskIds: [$task->getId()],
            ))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    public function testSummaryReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createActiveTask($this->demoUser->getId(), 'Build feature');
        $this->bed->createTaskGroupForTask($task->getId(), $this->demoUser->getId(), 'Release 1.0');

        $this->client->request('GET', '/api/task-groups/summary', server: $this->demoAuthorizedServer());

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testTaskGroupsRequireAuthentication(): void
    {
        $client = $this->createClientAndResetDatabase();

        $client->request('POST', '/api/task-groups');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }
}
