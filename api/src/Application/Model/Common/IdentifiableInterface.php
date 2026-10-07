<?php

declare(strict_types=1);

namespace App\Application\Model\Common;

interface IdentifiableInterface
{
    public function getId(): string;
}
