<?php

declare(strict_types=1);

namespace App\Shared\Utils;

use LogicException;
use Psr\Container\ContainerInterface;

final class ContainerUtils
{
    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    public static function get(ContainerInterface $container, string $id): object
    {
        $service = $container->get($id);

        if (!is_object($service) || !$service instanceof $id) {
            throw new LogicException(sprintf('Container service "%s" must be an instance of %s, %s given.', $id, $id, get_debug_type($service)));
        }

        return $service;
    }
}
