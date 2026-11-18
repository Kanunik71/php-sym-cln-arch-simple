<?php

declare(strict_types=1);

namespace App\Tests\Application\Event\TaskGroup;

use App\Application\Event\TaskGroup\TaskGroupsRecalculationByGroupIdsRequestedEvent;
use PHPUnit\Framework\TestCase;

final class TaskGroupsRecalculationByGroupIdsRequestedEventTest extends TestCase
{
    public function testPayloadIsDeduplicatedAndSorted(): void
    {
        $event = new TaskGroupsRecalculationByGroupIdsRequestedEvent(['group-2', 'group-1', 'group-2']);

        self::assertSame(['group-1', 'group-2'], $event->taskGroupIds);
    }

    public function testEmptyPayloadStaysEmpty(): void
    {
        $event = new TaskGroupsRecalculationByGroupIdsRequestedEvent([]);

        self::assertSame([], $event->taskGroupIds);
    }
}
