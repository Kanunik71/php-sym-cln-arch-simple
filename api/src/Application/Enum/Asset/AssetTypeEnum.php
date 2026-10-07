<?php

declare(strict_types=1);

namespace App\Application\Enum\Asset;

enum AssetTypeEnum: string
{
    case Virtual = 'Virtual';
    case Physical = 'Physical';
}
