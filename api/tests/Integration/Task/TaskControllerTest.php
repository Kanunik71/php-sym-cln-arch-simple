<?php

declare(strict_types=1);

namespace App\Tests\Integration\Task;

use App\Application\Enum\Task\TaskStatusEnum;
use App\Application\Exception\ErrorCodeEnum;
use App\Application\Model\Task\Action\TaskChangeStatusModel;
use App\Application\Model\Task\Action\TaskCreateModel;
use App\Application\Model\Task\Read\TaskPageModel;
use App\Application\Model\Task\Read\TaskViewModel;
use App\Shared\Utils\UidUtils;
use App\Tests\AuthenticatedWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/** Presentation: HTTP status + error codeId. Use-case — Application Command/Query + TestBed. */
final class TaskControllerTest extends AuthenticatedWebTestCase
{
    public function testListTasksReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Learn Symfony');

        $this->client->request('GET', '/api/tasks', server: $this->demoAuthorizedServer());

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $page = $this->decodeModel($this->client, TaskPageModel::class);
        $this->assertSame(1, $page->page);
        $this->assertSame(20, $page->perPage);
        $this->assertSame(1, $page->total);
    }

    public function testListRejectsInvalidPerPage(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request('GET', '/api/tasks?perPage=0', server: $this->demoAuthorizedServer());

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertSame(ErrorCodeEnum::ValidationError, $this->decodeErrorModel($this->client)->codeId);
    }

    public function testGetTaskReturnsOk(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Learn Symfony');

        $this->client->request(
            'GET',
            '/api/tasks/'.$task->getId(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $view = $this->decodeModel($this->client, TaskViewModel::class);
        $this->assertSame($task->getId(), $view->id);
        $this->assertSame('Learn Symfony', $view->name);
        $this->assertSame(TaskStatusEnum::Initial, $view->status);
        $this->assertSame($this->demoUser->getId(), $view->ownerId);
    }

    public function testGetUnknownTaskReturnsNotFound(): void
    {
        $this->createAuthenticatedClient();

        $this->client->request(
            'GET',
            '/api/tasks/'.UidUtils::nil(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertSame(ErrorCodeEnum::TaskNotFound, $this->decodeErrorModel($this->client)->codeId);
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

        $created = $this->decodeModel($this->client, TaskViewModel::class);
        $this->assertSame('Learn Symfony', $created->name);
        $this->assertSame(TaskStatusEnum::Initial, $created->status);
        $this->assertSame($this->demoUser->getId(), $created->ownerId);
        $this->assertSame([], $created->users);
        $this->assertNull($created->parentId);
        $this->assertNull($created->estimateTime);
    }

    public function testCreateTaskRejectsEmptyName(): void
    {
        $this->createAuthenticatedClient();

        $this->requestJson(
            $this->client,
            'POST',
            '/api/tasks',
            ['name' => ''],
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertSame(ErrorCodeEnum::ValidationError, $this->decodeErrorModel($this->client)->codeId);
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
        $task = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Learn Symfony');

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

        $view = $this->decodeModel($this->client, TaskViewModel::class);
        $this->assertSame(TaskStatusEnum::Active, $view->status);
        $this->assertSame(120, $view->estimateTime);
    }

    public function testChangeStatusRejectsSelfTransition(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Learn Symfony');

        $this->requestJson(
            $this->client,
            'PATCH',
            '/api/tasks/'.$task->getId().'/status',
            (new TaskChangeStatusModel(status: TaskStatusEnum::Initial))->toArray(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertSame(ErrorCodeEnum::ValidationError, $this->decodeErrorModel($this->client)->codeId);
    }

    public function testInvalidStatusTransitionReturnsConflict(): void
    {
        $this->createAuthenticatedClient();
        $task = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Blocked');

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
        $task = $this->bed->createTask(ownerId: $this->demoUser->getId(), name: 'Learn Symfony');

        $this->client->request(
            'DELETE',
            '/api/tasks/'.$task->getId(),
            server: $this->demoAuthorizedServer(),
        );

        $this->assertSame(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }
}
