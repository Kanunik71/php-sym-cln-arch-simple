<?php

declare(strict_types=1);

namespace App\Tests\Support\Trait\Model;

use App\Application\Model\TaskGroup\TaskGroupModel;
use App\Application\Model\TaskGroup\Read\TaskGroupViewModel;
use App\Shared\Utils\DateUtils;

trait AssertsTaskGroupModelTrait
{
    private function assertTaskGroupModelMatches(TaskGroupModel $taskGroup, TaskGroupViewModel $model): void
    {
        $this->assertSame($taskGroup->id, $model->id);
        $this->assertSame($taskGroup->ownerId, $model->ownerId);
        $this->assertSame($taskGroup->name, $model->name);
        $this->assertSame($taskGroup->status, $model->status);
        $this->assertSame(DateUtils::toAtom($taskGroup->createdAt), $model->createdAt);
    }
}
