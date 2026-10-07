<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\TaskGroup\Query;

use App\Application\Service\TaskGroup\Query\TaskGroupSummarizeQueryService;
use App\Tests\DatabaseTestCase;

final class TaskGroupSummarizeQueryServiceTest extends DatabaseTestCase
{
    public function testReturnsStatusCountsPerGroup(): void
    {
        $user = $this->bed->seedDefaultUser();

        $initialTask = $this->bed->createTask(name: 'Initial task');
        $activeTask = $this->bed->createActiveTask($user->getId(), 'Active task');
        $finishedTask = $this->bed->createFinishedTask($user->getId(), 'Finished task');
        $canceledTask = $this->bed->createCanceledTask($user->getId(), 'No longer needed', 'Canceled task');

        $release = $this->bed->createTaskGroup(ownerId: $user->getId(), name: 'Release 1.0');
        $this->bed->linkTaskGroupToTask($release, $initialTask->getId());
        $this->bed->linkTaskGroupToTask($release, $activeTask->getId());
        $this->bed->linkTaskGroupToTask($release, $finishedTask->getId());

        $empty = $this->bed->createTaskGroup(ownerId: $user->getId(), name: 'Empty group');

        $backlog = $this->bed->createTaskGroup(ownerId: $user->getId(), name: 'Backlog');
        $this->bed->linkTaskGroupToTask($backlog, $canceledTask->getId());

        $summarizeTaskGroups = $this->bed->get(TaskGroupSummarizeQueryService::class);

        $summary = $summarizeTaskGroups->execute();

        $byId = [];
        foreach ($summary as $item) {
            $byId[$item->id] = $item;
        }

        $this->assertArrayHasKey($release->getId(), $byId);
        $this->assertSame('Release 1.0', $byId[$release->getId()]->name);
        $this->assertSame(1, $byId[$release->getId()]->taskStatusCounts->initial);
        $this->assertSame(1, $byId[$release->getId()]->taskStatusCounts->active);
        $this->assertSame(0, $byId[$release->getId()]->taskStatusCounts->canceled);
        $this->assertSame(1, $byId[$release->getId()]->taskStatusCounts->finished);

        $this->assertArrayHasKey($empty->getId(), $byId);
        $this->assertSame(0, $byId[$empty->getId()]->taskStatusCounts->initial);
        $this->assertSame(0, $byId[$empty->getId()]->taskStatusCounts->active);
        $this->assertSame(0, $byId[$empty->getId()]->taskStatusCounts->canceled);
        $this->assertSame(0, $byId[$empty->getId()]->taskStatusCounts->finished);

        $this->assertArrayHasKey($backlog->getId(), $byId);
        $this->assertSame(0, $byId[$backlog->getId()]->taskStatusCounts->initial);
        $this->assertSame(0, $byId[$backlog->getId()]->taskStatusCounts->active);
        $this->assertSame(1, $byId[$backlog->getId()]->taskStatusCounts->canceled);
        $this->assertSame(0, $byId[$backlog->getId()]->taskStatusCounts->finished);
    }
}
