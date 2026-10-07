<?php

declare(strict_types=1);

namespace App\Application\Enum\File;

enum FileStatusEnum: string
{
    case Temporary = 'Temporary';
    case Permanent = 'Permanent';
}
