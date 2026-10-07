<?php

declare(strict_types=1);

namespace App\Tests\Integration\Task;

use App\Application\Model\Task\Action\TaskChangeStatusModel;
use App\Application\Model\Task\Action\TaskCreateModel;
use App\Application\Exception\ErrorCodeEnum;
use App\Application\Enum\Task\TaskStatusEnum;
use App\Shared\Utils\UidUtils;
use App\Tests\AuthenticatedWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/** Presentation: HTTP status + error codeId. Use-case — Application Command/Query + TestBed. */
final class TaskControllerTest extends AuthenticatedWebTestCase
{
    public function testListTasksReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $this->bed->createTask(name: 'Learn Symfony');

        $this->client->request('GET', '/api/tasks', server: $this->demoAuthorizedServer());

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testGetTaskReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createTask(name: 'Learn Symfony');

        $this->client->request(
            'GET',
            '/api/tasks/'.$task->getId(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testTasksRequireAuthentication(): void
    {
        $client = $this->createClientAndResetDatabase();

        $client->request('GET', '/api/tasks');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testCreateTaskReturnsCreated(): void
    {
        $this->createAuthenticatedClient();

        $this->requestJson(
            $this->client,
            'POST',
            '/api/tasks',
            (new TaskCreateModel(name: 'Learn Symfony'))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateChildTaskWithUnknownParentReturnsNotFound(): void
    {
        $this->createAuthenticatedClient();

        $this->requestJson(
            $this->client,
            'POST',
            '/api/tasks',
            (new TaskCreateModel(name: 'Orphan child', parentId: UidUtils::nil()))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertSame(ErrorCodeEnum::TaskNotFound, $this->decodeErrorModel($this->client)->codeId);
    }

    public function testChangeStatusReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createTask(name: 'Learn Symfony');

        $this->requestJson(
            $this->client,
            'PATCH',
            '/api/tasks/'.$task->getId().'/status',
            (new TaskChangeStatusModel(
                status: TaskStatusEnum::Active,
                estimateTime: 120,
            ))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testInvalidStatusTransitionReturnsConflict(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createTask(name: 'Blocked');

        $this->requestJson(
            $this->client,
            'PATCH',
            '/api/tasks/'.$task->getId().'/status',
            (new TaskChangeStatusModel(status: TaskStatusEnum::Finished))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
        $this->assertSame(ErrorCodeEnum::InvalidTaskStatusTransition, $this->decodeErrorModel($this->client)->codeId);
    }

    public function testCannotChangeStatusOfAnotherUsersTask(): void
    {
        $this->createAuthenticatedClient(email: 'owner@example.com');
        $task = $this->bed->createActiveTask($this->demoUser->getId(), 'Owner task', 15);

        $this->createUser(email: 'other@example.com');
        $otherToken = $this->loginAs('other@example.com');

        $this->requestJson(
            $this->client,
            'PATCH',
            '/api/tasks/'.$task->getId().'/status',
            (new TaskChangeStatusModel(status: TaskStatusEnum::Canceled, cancelReason: 'Not mine'))->toArray(),
            server: $this->authorizedServer($otherToken),
        );

        $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
        $this->assertSame(ErrorCodeEnum::UnauthorizedTaskAccess, $this->decodeErrorModel($this->client)->codeId);
    }

    public function testDeleteTaskReturnsNoContent(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createTask(name: 'Learn Symfony');

        $this->client->request(
            'DELETE',
            '/api/tasks/'.$task->getId(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }
}
