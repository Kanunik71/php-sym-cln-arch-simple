<?php

declare(strict_types=1);

namespace App\Application\Enum\TaskGroup;

enum TaskGroupStatusEnum: string
{
    case Initial = 'Initial';
    case InProgress = 'InProgress';
    case Completed = 'Completed';
}
