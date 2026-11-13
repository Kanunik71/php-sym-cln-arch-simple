<?php

declare(strict_types=1);

namespace App\Tests\Application\Service\TaskGroup\Query;

use App\Application\Service\TaskGroup\Query\TaskGroupSummarizeQueryService;
use App\Tests\DatabaseTestCase;

final class TaskGroupSummarizeKeysetTest extends DatabaseTestCase
{
    /**
     * The walk must return every group exactly once, even when the table
     * holds more rows than a single batch.
     */
    public function testWalksEveryGroupWithoutDuplicates(): void
    {
        $user = $this->bed->seedDefaultUser();

        $expectedIds = [];
        for ($i = 0; $i < 25; ++$i) {
            $group = $this->bed->createTaskGroup(ownerId: $user->getId(), name: 'Group '.$i);
            $expectedIds[] = $group->getId();
        }

        // batch size 10 => the walk spans three batches
        $service = new TaskGroupSummarizeQueryService(
            $this->bed->taskGroupRepository(),
            10,
        );

        $summary = $service->execute();

        $summaryIds = array_map(static fn ($item): string => $item->id, $summary);

        $this->assertCount(count($expectedIds), $summaryIds);
        $this->assertSame(count($expectedIds), count(array_unique($summaryIds)));

        foreach ($expectedIds as $expectedId) {
            $this->assertContains($expectedId, $summaryIds);
        }
    }

    /**
     * Groups are summarised newest first — ids are UUID v7 and grow with
     * creation time, so descending id is descending creation time.
     */
    public function testReturnsGroupsNewestFirst(): void
    {
        $user = $this->bed->seedDefaultUser();

        $first = $this->bed->createTaskGroup(ownerId: $user->getId(), name: 'First');
        $second = $this->bed->createTaskGroup(ownerId: $user->getId(), name: 'Second');
        $third = $this->bed->createTaskGroup(ownerId: $user->getId(), name: 'Third');

        $service = new TaskGroupSummarizeQueryService(
            $this->bed->taskGroupRepository(),
            2,
        );

        $summary = $service->execute();

        $ids = array_map(static fn ($item): string => $item->id, $summary);

        $this->assertSame([$third->getId(), $second->getId(), $first->getId()], $ids);
    }

    /**
     * The cursor is the boundary of the previous batch: the last id of a
     * batch is the first id of the next one, with nothing skipped between.
     */
    public function testCursorContinuesWherePreviousBatchEnded(): void
    {
        $user = $this->bed->seedDefaultUser();

        $ids = [];
        for ($i = 0; $i < 12; ++$i) {
            $ids[] = $this->bed->createTaskGroup(ownerId: $user->getId(), name: 'Group '.$i)->getId();
        }

        $repository = $this->bed->taskGroupRepository();

        $first = $repository->listBatchBefore('ffffffff-ffff-ffff-ffff-ffffffffffff', 5);
        $this->assertCount(5, $first);

        $cursor = $first[count($first) - 1]->getId();

        $second = $repository->listBatchBefore($cursor, 5);
        $this->assertCount(5, $second);

        // the boundary row itself is not returned twice
        $secondIds = array_map(static fn ($group): string => $group->getId(), $second);
        $this->assertNotContains($cursor, $secondIds);

        // and the walk is strictly descending
        $firstIds = array_map(static fn ($group): string => $group->getId(), $first);
        $this->assertGreaterThan($firstIds[count($firstIds) - 1], $firstIds[0]);
    }
}
