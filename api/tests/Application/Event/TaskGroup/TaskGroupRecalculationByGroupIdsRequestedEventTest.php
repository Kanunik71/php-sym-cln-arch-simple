<?php

declare(strict_types=1);

namespace App\Tests\Application\Event\TaskGroup;

use App\Application\Event\TaskGroup\TaskGroupRecalculationByGroupIdsRequestedEvent;
use PHPUnit\Framework\TestCase;

final class TaskGroupRecalculationByGroupIdsRequestedEventTest extends TestCase
{
    public function testPayloadIsDeduplicatedAndSorted(): void
    {
        $event = new TaskGroupRecalculationByGroupIdsRequestedEvent(['group-2', 'group-1', 'group-2']);

        self::assertSame(['group-1', 'group-2'], $event->taskGroupIds);
    }

    public function testEmptyPayloadStaysEmpty(): void
    {
        $event = new TaskGroupRecalculationByGroupIdsRequestedEvent([]);

        self::assertSame([], $event->taskGroupIds);
    }
}
