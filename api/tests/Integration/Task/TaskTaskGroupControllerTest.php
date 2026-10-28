<?php

declare(strict_types=1);

namespace App\Tests\Integration\Task;

use App\Application\Exception\ErrorCodeEnum;
use App\Application\Model\TaskGroup\Action\TaskGroupCreateModel;
use App\Shared\Utils\UidUtils;
use App\Tests\AuthenticatedWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/** Presentation: HTTP status + error codeId. Use-case — Application Command/Query + TestBed. */
final class TaskTaskGroupControllerTest extends AuthenticatedWebTestCase
{
    public function testGetTaskGroupReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createFinishedTask($this->demoUser->getId(), 'Build feature', 60);
        $group = $this->bed->createTaskGroupForTask($task->getId(), $this->demoUser->getId(), 'Release 1.0');

        $this->client->request(
            'GET',
            '/api/tasks/'.$task->getId().'/task-groups/'.$group->getId(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testListTaskGroupsReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Build feature');
        $this->bed->createTaskGroupForTask($task->getId(), $this->demoUser->getId(), 'Release 1.0');

        $this->client->request(
            'GET',
            '/api/tasks/'.$task->getId().'/task-groups',
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testTaskTaskGroupsRequireAuthentication(): void
    {
        $client = $this->createClientAndResetDatabase();

        $client->request('GET', '/api/tasks/'.UidUtils::nil().'/task-groups');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testCreateTaskGroupReturnsCreated(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Build feature');

        $this->requestJson(
            $this->client,
            'POST',
            '/api/tasks/'.$task->getId().'/task-groups',
            (new TaskGroupCreateModel(name: 'Release 1.0'))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    public function testAttachTaskGroupReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Build feature');
        $otherTask = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Other feature');
        $group = $this->bed->createTaskGroupForTask($otherTask->getId(), $this->demoUser->getId(), 'Release 1.0');

        $this->client->request(
            'POST',
            '/api/tasks/'.$task->getId().'/task-groups/'.$group->getId().'/attach',
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testAttachTaskGroupReturnsForbiddenForNonOwner(): void
    {
        $this->createAuthenticatedClient(email: 'owner@example.com');
        $task = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Build feature');
        $otherTask = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Other feature');
        $group = $this->bed->createTaskGroupForTask($otherTask->getId(), $this->demoUser->getId(), 'Release 1.0');

        $this->createUser(email: 'other@example.com');
        $otherToken = $this->loginAs('other@example.com');

        $this->client->request(
            'POST',
            '/api/tasks/'.$task->getId().'/task-groups/'.$group->getId().'/attach',
            server: $this->authorizedServer($otherToken),
        );

        $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        $this->assertSame(
            ErrorCodeEnum::UnauthorizedTaskGroupAccess,
            $this->decodeErrorModel($this->client)->codeId,
        );
    }

    public function testDeleteTaskGroupReturnsForbiddenForNonOwner(): void
    {
        $this->createAuthenticatedClient(email: 'owner@example.com');
        $task = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Build feature');
        $group = $this->bed->createTaskGroupForTask($task->getId(), $this->demoUser->getId(), 'Release 1.0');

        $this->createUser(email: 'other@example.com');
        $otherToken = $this->loginAs('other@example.com');

        $this->client->request(
            'DELETE',
            '/api/tasks/'.$task->getId().'/task-groups/'.$group->getId(),
            server: $this->authorizedServer($otherToken),
        );

        $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        $this->assertSame(
            ErrorCodeEnum::UnauthorizedTaskGroupAccess,
            $this->decodeErrorModel($this->client)->codeId,
        );
    }

    public function testRemoveTaskFromTaskGroupReturnsForbiddenForNonOwner(): void
    {
        $this->createAuthenticatedClient(email: 'owner@example.com');
        $task = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Build feature');
        $group = $this->bed->createTaskGroupForTask($task->getId(), $this->demoUser->getId(), 'Release 1.0');

        $this->createUser(email: 'other@example.com');
        $otherToken = $this->loginAs('other@example.com');

        $this->client->request(
            'DELETE',
            '/api/tasks/'.$task->getId().'/task-groups/'.$group->getId().'/tasks/'.$task->getId(),
            server: $this->authorizedServer($otherToken),
        );

        $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        $this->assertSame(
            ErrorCodeEnum::UnauthorizedTaskGroupAccess,
            $this->decodeErrorModel($this->client)->codeId,
        );
    }

    public function testDeleteTaskGroupReturnsNoContent(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Build feature');
        $group = $this->bed->createTaskGroupForTask($task->getId(), $this->demoUser->getId(), 'Release 1.0');

        $this->client->request(
            'DELETE',
            '/api/tasks/'.$task->getId().'/task-groups/'.$group->getId(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }
}
