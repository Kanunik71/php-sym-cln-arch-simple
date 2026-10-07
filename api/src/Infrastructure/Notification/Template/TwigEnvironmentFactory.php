<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Template;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigEnvironmentFactory
{
    public static function create(string $templatesPath): Environment
    {
        return new Environment(new FilesystemLoader($templatesPath));
    }
}
